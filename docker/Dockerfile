FROM million12/typo3-flow-neos-abstract:latest
MAINTAINER Dmitri Pisarev dimaip@gmail.com

ENV \
  T3APP_BUILD_REPO_URL="https://github.com/sfi-ru/KateheoDistr" \
  T3APP_BUILD_BRANCH=master \
  T3APP_NEOS_SITE_PACKAGE=Sfi.Site \
  T3APP_USE_SURF_DEPLOYMENT=true

# Pre-install Neos CMS
RUN . /build-typo3-app/pre-install-typo3-app.sh

