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

    /**
     * attributs
     *  @var map : carte (objet Leaflet)
     *  @var markers : ensemble des marqueurs de la carte
     *  @var typeCombobox : selection du type de prélevement dans l'onglet de filtrage
     */
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
            markers = L.layerGroup().addTo(map);
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
            data.forEach(function(k, v) {
                long = APP.modules.utility.convertDMSToDD(k.LONGITUDE.replace(/\s+/g, ''));
                lat = APP.modules.utility.convertDMSToDD(k.LATITUDE.replace(/\s+/g, ''));
                markers.addLayer(L.marker([lat, long], {icon: stationIcon}).bindLabel(k.ABBREVIATION));
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

        /**
         * methode permettant d'actualiser la carte
         * en fonction des options de filtrage
         */
        refresh : function() {
            type = typeCombobox.val();
            APP.modules.map.clearMarkers();
            APP.modules.service.getStations(APP.modules.map.affichageStations, type);
        }

    }
})();

APP.modules.affichage =(function() {

    /**
     * @var typeCombobox : selection du type de prélevement dans l'onglet de filtrage
     */
    var typeCombobox = $('#typeCombobox');
    
    return {

        /**
         * Methode de slide du menu de filtrage
         */
        showFilterMenu : function() {
            $("#wrapper").toggle("slide", {direction : 'left'});
        },

        /**
         * Methode de recherche et d'affichage des differents
         * type de prelevements possible de selectionner
         * dans l'onglet de filtrage
         * @param data
         */
        initTypeCombobox : function(data) {
            data.forEach(function(k, v) {
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

        /**
         * methode AJAX permettant de recuperer une station
         * en particulier ou toutes les stations
         *
         * @param callback
         *          fonction de traitement des donnees
         * @param type
         *          type de prelevement (filtre)
         */
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

        /**
         * methode AJAX permettant de recuperer les types
         * de prélevement à filtrer
         *
         * @param callback
         *          fonction de traitement des donnees
         */
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

        convertDMSToDD : function(input) {
            var parts = input.split(/[^\d\w\.]+/);
            var dd = Number(parts[0]) + Number(parts[1])/60 + Number(parts[2])/(60*60);

            if (parts[3] == "S" || parts[3] == "W") {
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