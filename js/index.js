/**
 * namespace APP
 * détient les modules de l'application
 * ainsi qu'une methode d'initialisation
 *
 * @type {{modules, init}}
 */
var APP = (function() {
    return {
        modules: {},
        init: function () {

        }
    }
})();

/**
 * module MAP
 * modélise la carte et les fonctionnalités
 * associée
 *
 * @type {{init}}
 */
APP.modules.map = (function() {

    var map;

    return {

        /**
         * methode d'initialisation
         *
         * @param htmlContainer
         *          container html de la carte
         */
        init : function(htmlContainer) {
            map = L.map(htmlContainer, {
                center: [48.8534100, 2.3488000],
                zoom : 5
            });
            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        }

    }
})();

window.onload = (function () {
    APP.modules.map.init('map');
    APP.init();
})();