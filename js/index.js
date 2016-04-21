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
            APP.modules.service.getStations(APP.modules.map.affichageStations);
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
        },

        affichageStations : function(data) {
            data.forEach(function(k, v) {
                long = APP.modules.utility.parseDMS(k.LONGITUDE.replace(/\s+/g, ''));
                lat = APP.modules.utility.parseDMS(k.LATITUDE.replace(/\s+/g, ''));
                L.marker([lat, long]).addTo(map);
            });
        }

    }
})();

APP.modules.service = (function() {

    return {

        getStations : function(callback) {
            console.log("ok");
            $.ajax( {
                url : "index.php/api/stations",
                type : 'POST',
                dataType: 'json',
                success: callback,
            });
        }

    }

})();

APP.modules.utility = (function() {

    return {

        parseDMS : function(input) {
            var parts = input.split(/[^\d\w\.]+/);
            return APP.modules.utility.convertDMSToDD(parts[0], parts[1], parts[2], parts[3]);
        },

        convertDMSToDD : function(degrees, minutes, seconds, direction) {
            var dd = Number(degrees) + Number(minutes)/60 + Number(seconds)/(60*60);

            if (direction == "S" || direction == "W") {
                dd = dd * -1;
            } // Don't do anything for N or E
            return dd;
        }

    }

})();

window.onload = (function () {
    APP.modules.map.init('map');
    APP.init();
})();