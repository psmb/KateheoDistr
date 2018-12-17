document.querySelectorAll("a.js-TagsListBtn").forEach(el => {
    el.addEventListener("click", function(event){
        event.preventDefault();
        // start spinning
        document.querySelectorAll('.js-TagsListIcon').forEach(el => {
            el.classList.add('isSpinning');
        });

        fetch('/?showAllTags=true').then(response => response.text()).then(html => {
            document.querySelectorAll('.js-TagsList').forEach(el => {
                el.innerHTML = html + '<br><br>';
            });
        });
    }, false);
});
