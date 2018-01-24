<?php
namespace Sfi\Kateheo\Command;

/*																		*
 * This script belongs to the TYPO3 Flow package "Sfi.Kateheo".		 *
 *																		*
 *																		*/

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\ImageRepository;

/**
 * @Flow\Scope("singleton")
 */
class MigrationCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected $context;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var ImageRepository
	 */
	protected $imageRepository;

	protected $connection;

	/**
	 * Import everything
	 *
	 * @return string
	 */
	public function importAllCommand() {
		$this->importCategoriesCommand();
		$this->importNewsCommand();
		$this->importMediaCommand();
		$this->importRealurl();
	}

	/**
	 * Import categories
	 *
	 * @return string
	 */
	public function importCategoriesCommand() {
		$this->init();
		$this->importCategories();
		return "\nDone!\n";
	}

	/**
	 * Import news
	 *
	 * @return string
	 */
	public function importNewsCommand() {
		$newsNodeType = 'Sfi.Kateheo:News';
		$textNodeType = 'TYPO3.Neos.NodeTypes:Text';

		$this->init();
		$news = $this->getNews();
		foreach ($news as $newsItem) {
			$categoryNode = $this->getCategoryByOriginalId($newsItem['uid_foreign']);
			if (count($this->getNewsByOriginalId($newsItem['uid']))) {
				echo "Node ".$newsItem['uid']." already exists, skipped\n";
			} else if (!count($categoryNode)) {
				echo "Node ".$newsItem['uid']." has no category, skipped\n";
			} else {
				$newsNodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
				$newsNodeTemplate->setNodeType($this->nodeTypeManager->getNodeType($newsNodeType));
				$newsNodeTemplate->setProperty('originalIdentifier', $newsItem['uid']);
				$newsNodeTemplate->setProperty('title', $newsItem['title']);
				$newsNodeTemplate->setProperty('teaser', $newsItem['teaser']);
				$newsNodeTemplate->setProperty('author', $newsItem['author']);
				if ($newsItem['datetime']) {
					$date = new \DateTime();
					$date->setTimestamp($newsItem['datetime']);
					$newsNodeTemplate->setProperty('date', $date);
				}
				$newsNode = $categoryNode->createNodeFromTemplate($newsNodeTemplate);

				if ($newsItem['bodytext']) {
					$bodytext = $this->parseBodytext($newsItem['bodytext']);
					$mainContentNode = $newsNode->getNode('main');
					$bodytextTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
					$bodytextTemplate->setNodeType($this->nodeTypeManager->getNodeType($textNodeType));
					$bodytextTemplate->setProperty('text', $bodytext);
					$mainContentNode->createNodeFromTemplate($bodytextTemplate);
				}
				echo "Node " . $newsItem['uid'] . " migrated\n";
			}
		}
		return "Done!";
	}

	/**
	 * Import images
	 *
	 * @return string
	 */
	public function importMediaCommand() {
		$allowedImageFileTypes = array("jpg", "jpeg", "gif");
		$imageNodeType = 'TYPO3.Neos.NodeTypes:Image';
		$videoNodeType = 'Sfi.YouTube:YouTube';

		$this->init();
		$media = $this->getMedia();
		foreach ($media as $mediaItem) {
			$newsNode = $this->getNewsByOriginalId($mediaItem['parent']);
			if (!$newsNode) {
				echo "No news node found for mediaItem " . $mediaItem['parent'] . "\n";
			} else {
				$mainCollectionNode = $newsNode->getNode('main');
				$coverCollectionNode = $newsNode->getNode('coverCollection');
				if ($mediaItem['type'] == 0) {
					$imageFile = $mediaItem['image'];
					if (in_array(pathinfo(strtolower($imageFile), PATHINFO_EXTENSION), $allowedImageFileTypes)) {
						$file = realpath(dirname($_SERVER['PHP_SELF'])) . '/tx_news/' . $imageFile;
						if (!file_exists($file)) {
							echo "Missing file: " . $file . "\n";
						} else {
							$image = $this->importImage($file);
							$imageNodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
							$imageNodeTemplate->setNodeType($this->nodeTypeManager->getNodeType($imageNodeType));
							$imageNodeTemplate->setProperty('image', $image);
							if (isset($mediaItem['caption'])) {
								$imageNodeTemplate->setProperty('alternativeText', $mediaItem['caption']);
							}
							// If cover contentCollection already has an image, set other nodes to main
							if (count($coverCollectionNode->getChildNodes())){
								$mainCollectionNode->createNodeFromTemplate($imageNodeTemplate);
							} else {
								$coverCollectionNode->createNodeFromTemplate($imageNodeTemplate);
							}
							echo "- " . $imageFile . " imported\n";
						}
					} else {
						echo "Illegal mediaItem file extension of file: " . $imageFile . "\n";
					}
				} else if ($mediaItem['type'] == 1) {
					$videoUrl = $mediaItem['multimedia'];
					$videoTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
					$videoTemplate->setNodeType($this->nodeTypeManager->getNodeType($videoNodeType));
					$videoTemplate->setProperty('videoUrl', $videoUrl);
					$mainCollectionNode->createNodeFromTemplate($videoTemplate);
				} else {
					echo "Unknown media type: " . $mediaItem['type'] ." \n";
				}
			}
		}
		return "\nDone!\n";
	}


	/**
	 * Import related
	 *
	 * @return string
	 */
	public function importRelatedCommand() {
		$relatedField = 'author';

		$this->init();
		foreach ($this->getRelated() as $relation) {
			$localNews = $this->getNewsByOriginalId($relation['uid_local']);
			$foreignNews = $this->getNewsByOriginalId($relation['uid_foreign']);
			if ($localNews && $foreignNews) {
				echo $localNews->getProperty('title') . " --> " . $foreignNews->getProperty('title') . "\n";
				$localNews->setProperty($relatedField, $foreignNews);
			} else {
				echo "News nodes not found for relation\n";
			}
		}

		return "Done!";
	}


	/**
	 * Import realurl
	 *
	 * @return string
	 */
	public function importRealurlCommand() {
		$this->init();
		foreach ($this->getRealurl() as $realurl) {
			if ($realurl['tablename'] == 'tx_news_domain_model_news') {
				$news = $this->getNewsByOriginalId($realurl['value_id']);
				if ($news) {
					echo $realurl['value_alias'] . "\n";
					$news->setProperty('uriPathSegment', $realurl['value_alias']);
				} else {
					echo "No such news found\n";
				}
			} else {
				echo "Unknown tablename\n";
			}
		}

		return "Done!";
	}


	protected function init() {
		$this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
		$this->connection = $this->entityManager->getConnection();
	}

	protected function parseBodytext($bodytext) {
		$bodytext = preg_replace('@<(p|div|span|i|b|strong|em)[^>]*></\1>@ui', '', $bodytext);
		$bodytext = preg_replace('/^((?!<p>).+)$/uim', '<p>$1</p>', $bodytext);
		//Not well tested!
		$bodytext = preg_replace_callback(
			'@<link\s+(\S*)[^>]*>([^<]*)</link>@ui',
			function ($matches) {
				//If link to page, we drop that link, as they have changed anyways
				if(is_numeric($matches[1])){
					return $matches[2];
				}else if(preg_match('@http@ui', $matches[1], $matches2)){ //If url, then turn into normal link
					return '<a href="' . $matches[1] . '">' . $matches[2] . '</a>';
				}else if(preg_match('@(record:tt_news:)([\d]+)@ui', $matches[0], $matches2)){ //Remove links to news record:tt_news:2806
					return $matches[2];
				}else{ //just in case...
					return '<a href="' . $matches[1] . '">' . $matches[2] . '</a>';
				}
			},
			$bodytext
		);
		return $bodytext;
	}

	protected function importCategories($parentcategory = 0, $targetPath = '/sites/site') {
		$categoryNodeType = 'Sfi.Kateheo:Category';

		$sql = "SELECT * FROM tx_news_domain_model_category
			WHERE parentcategory=:parentcategory AND deleted=0 AND hidden=0
			ORDER BY 'sorting'";
		$statement = $this->connection->prepare($sql);
		$params = array('parentcategory' => $parentcategory);
		$statement->execute($params);
		foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $category) {
			$rootNode = $this->context->getNode($targetPath);

			$categoryNode = $this->getCategoryByOriginalId($category['uid']);
			if ($categoryNode) {
				echo "Node " . $category['uid'] . " already exists, skipped\n";
			} else {
				echo "$parentcategory -- $targetPath\n";
				$categoryNodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
				$categoryNodeTemplate->setNodeType($this->nodeTypeManager->getNodeType($categoryNodeType));
				$categoryNodeTemplate->setProperty('originalIdentifier', $category['uid']);
				$categoryNodeTemplate->setProperty('title', $category['title']);
				$categoryNode = $rootNode->createNodeFromTemplate($categoryNodeTemplate);
			}
			$this->importCategories($category['uid'], $categoryNode->getPath());
		}
	}

	protected function getCategoryByOriginalId($id) {
		$q = new FlowQuery(array($this->context->getRootNode()));
		return $q->find('[instanceof Sfi.Kateheo:Category]')->filter('[originalIdentifier = "' . $id  .'"]')->get(0);
	}

	protected function getNewsByOriginalId($id) {
		$q = new FlowQuery(array($this->context->getRootNode()));
		return $q->find('[instanceof Sfi.Kateheo:News]')->filter('[originalIdentifier = "' . $id  .'"]')->get(0);
	}

	protected function getNews() {
		$sql = "SELECT * FROM tx_news_domain_model_news as n
			LEFT JOIN tx_news_domain_model_news_category_mm as c
			ON n.uid = c.uid_local
			WHERE n.deleted=0 AND n.hidden=0
			ORDER BY 'datetime'";
		$statement = $this->connection->prepare($sql);
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function getMedia() {
		$sql = "SELECT * FROM tx_news_domain_model_media
			WHERE deleted=0 AND hidden=0 ORDER BY 'sorting'";
		$statement = $this->connection->prepare($sql);
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function getRelated() {
		$sql = "SELECT * FROM tx_news_domain_model_news_related_mm";
		$statement = $this->connection->prepare($sql);
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function getRealurl() {
		$sql = "SELECT * FROM tx_realurl_uniqalias";
		$statement = $this->connection->prepare($sql);
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function importImage($filename) {
		$resource = $this->resourceManager->importResource($filename);

		$image = new Image($resource);
		$this->imageRepository->add($image);

		$processingInstructions = Array();
		return $this->objectManager->get('TYPO3\Media\Domain\Model\ImageVariant', $image, $processingInstructions);
	}
}
