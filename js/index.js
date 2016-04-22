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
            APP.modules.service.getStations(APP.modules.map.affichageStations, "all");
            APP.modules.service.getTypes(APP.modules.affichage.initTypeCombobox);
            $('#filterButton').click(APP.modules.affichage.showFilterMenu);
            $('#refreshButton').click(APP.modules.map.refresh);
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

    var map, markers;
    var typeCombobox = $('#typeCombobox');

    return {

        /**
         * methode d'initialisation
         *
         * @param htmlContainer
         *          container html de la carte
         */
        init : function(htmlContainer) {
            map = L.map(htmlContainer, {
                center: [49.230141, 6.008881],
                zoom : 14
            });
            markers = L.layerGroup();
            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        },

        /**
         * methode d'affichage
         * @param data
         */
        affichageStations : function(data) {
            var stationIcon = L.icon({
                iconUrl: 'js/images/station-icon.png',
                iconRetinaUrl: 'js/images/station-icon-2x.png',
                iconSize: [25, 41], // size of the icon
                iconAnchor: [12, 40]
            });
            console.log(data.length);
            data.forEach(function(k, v) {
                long = APP.modules.utility.parseDMS(k.LONGITUDE.replace(/\s+/g, ''));
                lat = APP.modules.utility.parseDMS(k.LATITUDE.replace(/\s+/g, ''));
                markers.addLayer(L.marker([lat, long], {icon: stationIcon}).bindLabel(k.ABBREVIATION).addTo(map));
            });
        },

        /**
         * methode permettant de supprimer
         * tous les markers presents sur la map
         *
         */
        clearMarkers : function() {
          markers.clearLayers();
        },

        refresh : function() {
            console.log("click");
            type = typeCombobox.val();
            APP.modules.map.clearMarkers();
            APP.modules.service.getStations(APP.modules.map.affichageStations, type);
        }

    }
})();

APP.modules.affichage =(function() {
    
    var typeCombobox = $('#typeCombobox');
    
    return {

        showFilterMenu : function() {
            $("#wrapper").toggle("slide", {direction : 'left'});
        },
        
        initTypeCombobox : function(data) {
            data.forEach(function(k, v) {
                console.log(k);
                typeCombobox.append($('<option>', {
                    value: k,
                    text: k
                }));
            });
        }

    }

})();

/**
 * Base du REST
 * envoi les requêtes permettant
 * de récuperer les données du serveur
 * 
 * @type {{getStations}}
 */
APP.modules.service = (function() {

    return {

        getStations : function(callback, type) {
            addUrl = "/";
            if(type == "all") {
                addUrl = "";
            } else {
                addUrl = "/" + type;
            }
            $.ajax( {
                url : "index.php/api/stations" + addUrl,
                type : 'POST',
                dataType: 'json',
                success: callback
            });
        },
        
        getTypes : function(callback) {
            $.ajax( {
                url : "index.php/api/types",
                type : 'POST',
                dataType: 'json',
                success: callback
            });  
        }

    }

})();

/**
 * Module présentant les fonctions utilitaires
 * de l'application
 * 
 * @type {{parseDMS, convertDMSToDD}}
 */
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