var DataMarker = L.Marker.extend({
    data: {
        abbreviation: "custom"
    }
});

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
            APP.modules.service.getTypes(APP.modules.affichage.initTypeComboboxes);
            $('#filterButton').click(APP.modules.affichage.showFilterMenu);
            $('#refreshButton').click(APP.modules.map.refresh);
            $('.panel-heading').click(APP.modules.affichage.toggleElement);
            $('#refreshButton2').click(APP.modules.affichage.showAnalysis);
            $(document).on('click', '.list-group-item', APP.modules.affichage.selectAnalysis);
            $('#download').click(APP.modules.utility.downloadXLSX);
            $('.filtersSelect').on('change', APP.modules.affichage.showAnalysis);
            $('#typeFilterAnalysisCombobox').on('change', APP.modules.affichage.initFilterGroupAnalysisCombobox);
            $('#openButton').click(APP.modules.affichage.showModal);
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
    var map, markers, circles;
    var lastTypeCombobox = $('#typeCombobox');

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
            markers = {
                markersData : [],
                featureGroup : L.featureGroup().addTo(map),
            };
            circles = L.featureGroup().addTo(map);
            //markers.featureGroup.on('click', APP.modules.affichage.showStationInformations);
            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            map.on('click', APP.modules.affichage.closePanel);
        },

        /**
         * methode d'affichage
         * @param data
         */
        affichageStations : function(data) {
            var icon = 'station-icon.png';
            var icon2x = 'station-icon-2x.png';
            var type = APP.modules.utility.baseName(this.url);
            var stationIcon = L.icon({
                iconUrl: 'js/images/' + icon,
                iconRetinaUrl: 'js/images/' + icon2x,
                iconSize: [25, 41], // size of the icon
                iconAnchor: [12, 40]
            });
            var i = 0;
            data.forEach(function(k, v) {
                long = APP.modules.utility.convertDMSToDD(k.LONGITUDE.replace(/\s+/g, ''));
                lat = APP.modules.utility.convertDMSToDD(k.LATITUDE.replace(/\s+/g, ''));
                var newMarker = new DataMarker([lat, long], {icon: stationIcon}).bindLabel(k.ABBREVIATION);
                newMarker.on("click", APP.modules.affichage.showStationInformations);
                newMarker.options.abbreviation = k.ABBREVIATION;
                markers.featureGroup.addLayer(newMarker);
                sessionStorage.setItem(k.ABBREVIATION, JSON.stringify(k));
                i++;
            });
        },

        /**
         * methode permettant de supprimer
         * tous les markers presents sur la map
         *
         */
        clearMarkers : function() {
            markers.featureGroup.clearLayers();
        },

        /**
         * methode permettant de supprimer
         * tous les cercle de selection presents sur la map
         *
         */
        clearCircles : function() {
            circles.clearLayers();
        },

        /**
         * methode permettant d'ajouter un cercle de selection
         * sur la map
         *
         * @param latlng
         *             coordonnées du cercle et du marqueur cliqué
         */
        addCircle : function(latlng) {
            var circle = new L.Circle(latlng, 50, {
                color: 'black',
                opacity : 0.8,
                fillOpacity : 0.5
            });
            circles.addLayer(circle);
        },

        /**
         * methode permettant d'actualiser la carte
         * en fonction des options de filtrage
         */
        refresh : function() {
            if($(this).is('#refreshButton')) {
                lastTypeCombobox = $('#typeCombobox');
            }
            var type = lastTypeCombobox.val();
            APP.modules.map.clearMarkers();
            APP.modules.service.getStations(APP.modules.map.affichageStations, type);
        },

        /**
         * gestion des combobox de type de prélèvement
         * enregistre la dernière combobox modifiée
         *
         * @param lastCombobox
         *              dernière combobox modifiée
         */
        setLastTypeCombobox : function(lastCombobox) {
            lastTypeCombobox = lastCombobox;
        }

    }
})();

APP.modules.affichage = (function() {

    /**
     * @var typeCombobox : selection du type de prélevement dans l'onglet de filtrage
     */
    var typeCombobox = $('#typeCombobox');
    var currentSetting = [];
    var lastSetting = [];
    var listAnalysis = $('#list-analysis');
    var typeFilterAnalysisCombobox = $('#typeFilterAnalysisCombobox');
    var groupMeasuresCombobox = $('#groupMeasuresCombobox');
    var openPanelButton = $('#openInformation');

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
        initTypeComboboxes : function(data) {
            data.forEach(function(k, v) {
                typeFilterAnalysisCombobox.append($('<option>', {
                    value: k,
                    text: k
                }));
                typeCombobox.append($('<option>', {
                    value: k,
                    text: k
                }));
            });
            $('.selectpicker').selectpicker('refresh')
        },

        /**
         * méthode d'initialisation de la combobox
         * de choix du group de mesures dans le panel
         * filtre des informations de station
         */
        initFilterGroupAnalysisCombobox : function() {
            groupMeasuresCombobox.empty();
            var groupAnalysis = {"PSD" : "Particle Size Distribution", "MIN" : "", "EA" : "Element Analysis", "PAC" : "Polyclic Aromatic Compounds", "MIC" : "", "XRF" : "", "GP" : "Global Parameters", "ISO" : "", "DMT" : "Donnan Membrane Technique", "16S-MGE" : "", "ECOLI-ENT" : "", "PHAGE" : "", "QMJ" : "Daily Integrated Flow", "QTVAR" : "Instantaneous Flow", "CAMPY-VIRO" : "", "MET-HAP" : ""};
            switch(typeFilterAnalysisCombobox.val()) {
                case "all" :
                    break;
                case "sediment" :
                    groupAnalysis = {"GP" : "Global Parameters", "EA" : "Element Analysis", "PSD" : "Particle Size Distribution", "XRF" : "", "PAC" : "Polyclic Aromatic Compounds"};
                    break;
                case "hydrology" :
                    groupAnalysis = {"QMJ" : "Daily Integrated Flow", "QTVAR" : "Instantaneous Flow"};
                    break;
                case "spm" :
                    groupAnalysis = {"EA" : "Element Analysis"};
                    break;
                case "water" :
                    groupAnalysis = {"EA" : "Element Analysis", "GP" : "Global Parameters", "16S-MGE" : "", "PHAGE" : "", "PAC" : "Polyclic Aromatic Compounds"};
                    break;
            }
            for (var index in groupAnalysis) {
                groupMeasuresCombobox.append($('<option>', {
                    value: index,
                    html: index + " : <i>" + groupAnalysis[index] + "</i>"
                }));
            }
            groupMeasuresCombobox.attr('disabled', false);
            groupMeasuresCombobox.selectpicker('refresh');
        },

        /**
         * methode d'ouverture du panel
         * informatif de la station clickée
         *
         * @param e
         */
        showStationInformations : function(e) {
            var abb = e.target.options.abbreviation;
            if($.isEmptyObject(lastSetting)) {
                if(openPanelButton.is(":visible")) {
                    openPanelButton.toggle("slide", {direction : 'right'});
                }
                showPanel();
            } else {
                if(abb != lastSetting["station"]) {
                    if(openPanelButton.is(":visible")) {
                        openPanelButton.toggle("slide", {direction : 'right'});
                    }
                    showPanel();
                }
            }

            function showPanel() {
                APP.modules.map.clearCircles();
                var latlng = e.latlng;
                APP.modules.map.addCircle(latlng);
                listAnalysis.empty();
                currentSetting["station"] = abb;
                var station = JSON.parse(sessionStorage.getItem(abb));
                var informationDiv = $("#information");
                var titre = $('#titre');
                var nomStation = $('#nomStation');
                var description = $('#description');
                var filtreDiv = $("#filtres");
                var analysesDiv = $("#analyses");
                if(informationDiv.is(":hidden")) {
                    titre.text("Station : " + station.ABBREVIATION);
                    //description.text(station.DESCRIPTION);
                    description.text("Nunc non rutrum odio. Sed commodo massa sed pulvinar tristique. In luctus libero at arcu tincidunt, ut posuere nisi gravida. Nullam blandit vitae justo laoreet gravida. Fusce nec urna sit amet tellus maximus suscipit. Nam eget laoreet ante. Quisque sapien purus, pellentesque id magna eu, consectetur suscipit erat.");
                    nomStation.text(station.NAME);
                    informationDiv.toggle("slide", {direction : 'right'});
                } else if(titre.text() !== ("Station : " + station.ABBREVIATION)) {
                    informationDiv.toggle("slide", {direction : 'right'});
                    titre.text("Station : " + station.ABBREVIATION);
                    //description.text(station.DESCRIPTION);
                    description.text("Nunc non rutrum odio. Sed commodo massa sed pulvinar tristique. In luctus libero at arcu tincidunt, ut posuere nisi gravida. Nullam blandit vitae justo laoreet gravida. Fusce nec urna sit amet tellus maximus suscipit. Nam eget laoreet ante. Quisque sapien purus, pellentesque id magna eu, consectetur suscipit erat.");
                    nomStation.text(station.NAME);
                    informationDiv.toggle("slide", {direction : 'right'});
                }
                if($('#stationInfosBody').is(":hidden")) {
                    titre.trigger("click");
                } else {
                    if($('#analysesBody').is(":visible")) analysesDiv.trigger("click");
                }
                if(filtreDiv.next('.panel-body').is(":visible")) filtreDiv.trigger("click");
            }
        },

        /**
         * méthode de gestion d'affichage du slide
         * des menus d'information concernant une station
         * @param e
         *          information liée au click (fonction callback)
         */
        toggleElement : function(e) {
            var targetDom = $(e.delegateTarget);
            targetDom.find("div.glyphicon").toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
            var elementToToggle= targetDom.next('.panel-body');
            if(targetDom.is("#analyses") ) {
                if(elementToToggle.is(":hidden")) {
                    $('#refreshButton2').trigger("click");
                    var stationInfos = $('#stationInfosBody');
                    if (stationInfos.is(":visible")) {
                        $('#titre').trigger('click');
                    }
                }
                $('#staticButton').slideToggle('slow');
            }
            var analysis = $("#analysesBody");
            if(targetDom.is('#stationInfos') && elementToToggle.is(":hidden") && analysis.is(":visible")) {
                $('#analyses').trigger('click');
            }
            elementToToggle.slideToggle("slow");
        },

        /**
         * méthode de gestion des filtres et de lancement
         * de la récupération des analyses liées à la station
         */
        showAnalysis : function() {
            if($(this).is('#typeFilterAnalysisCombobox')) {
                APP.modules.map.setLastTypeCombobox($('#typeFilterAnalysisCombobox'));
                APP.modules.map.refresh();
            }
            listAnalysis.empty();
            var station = currentSetting["station"];
            var type = typeFilterAnalysisCombobox.val();
            var groupeMesure = groupMeasuresCombobox.val();
            if(type === "all") type = null;
            if(groupeMesure === "all") groupeMesure = null;
            if(currentSetting != lastSetting) {
                APP.modules.service.getAnalysisNames(APP.modules.affichage.showAnalysisField, station, type, groupeMesure);
                lastSetting['station'] = station;
            }
        },

        /**
         * méthode d'affichage des analyses
         * @param data
         *          données (analyses) reçu via AJAX
         */
        showAnalysisField : function(data) {
            data.forEach(function (k, v) {
                listAnalysis.append($('<li>', {
                    value: k._id,
                    text: k._id,
                    class: 'list-group-item'
                }));
            });
            $('#notfoundimg').hide();
            listAnalysis.show(500);
        },

        /**
         * méthode de selection d'une analyse
         */
        selectAnalysis : function() {
            var element = $(this);
            $('#list-analysis').find('.active').removeClass('active');
            element.toggleClass('active');
        },

        /**
         * méthode permettant de cacher le panneau
         * d'information concernant la station
         * et d'afficher un bouton de réouverture
         */
        closePanel : function() {
            var informationDiv = $('#information');
            if(informationDiv.is(":visible")) {
                if(openPanelButton.is(":hidden")) {
                    openPanelButton.click(function() {
                        if(informationDiv.is(':hidden')) {
                            informationDiv.toggle("slide", {direction: 'right'});
                            openPanelButton.toggle("slide", {direction: 'right'});
                        }
                    });
                    openPanelButton.toggle("slide", {direction : 'right'});
                }
                informationDiv.toggle("slide", {direction: 'right'});
            }
        },

        /**
         * methode permet d'afficher le pop-up des données
         * du fichier d'analyse choisis
         *
         */
        showModal : function() {
            var champ = $('#list-analysis').find('.active');
            if(champ !== null) {
                var fileName = champ.text();
                $('.modal-title').text(fileName + " [DATA]");
                APP.modules.service.getAnalysisData(APP.modules.affichage.createDataTable, fileName);
                $('#modalData').modal();
            }
        },

        /**
         * methode callback ajax
         * ajout des données
         * @param data
         */
        createDataTable : function(data) {
            var i = 0;
            var dataTable = $("#data-table");
            dataTable.empty();
            if (data != null) {
                data.SAMPLES.forEach(function(k, v) {
                    if(i == 0) {
                        dataTable.append($('<tr>'));
                    }
                    dataTable.append($('<tr>'));
                    for(var property in k) {
                        if(i==0) {
                            dataTable.find("tr:first").append($('<th>', {
                                text: property
                            }));
                        }
                        $("#data-table").find("tr:last")
                            .append($('<td>', {
                                text : k[property]
                            }));
                    }
                    i++;
                })
            }
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
            var addUrl = "/";
            if(type == "all") {
                addUrl = "";
            } else {
                addUrl = "/" + type;
            }
            $.ajax( {
                url : "index.php/api/stations" + addUrl,
                type : 'POST',
                dataType: 'json',
                success: callback,
                error : function() {
                    $.notify( {
                        message : "Station's recovery impossible.",
                        type : 'warning'
                    });
                }
            });
        },

        /**
         * methode AJAX permettant de recuperer les types
         * de prélevement à filtrer
         *
         * @param callback
         *          fonction de traitement des donnees
         *
         */
        getTypes : function(callback) {
            $.ajax( {
                url : "index.php/api/types",
                type : 'POST',
                dataType: 'json',
                success: callback,
                error : function(xhr, error) {
                    console.log(xhr, error);
                    $('body').notify( {
                        message : "Sample kind's recovery impossible",
                        type : 'warning'
                    });
                }
            });
        },

        /**
         * methode AJAX permettant de récuperer les noms
         * des analyses
         * @param callback
         * @param station
         *          station liée
         * @param type
         *          filtre type de prélevement
         * @param groupe
         *          filtre groupe de mesure
         */
        getAnalysisNames : function(callback, station, type, groupe) {
            var url = "index.php/api/analysis/intro/" + station;
            if (type) url += "/" + type; else url += "/null";
            if (groupe) url += "/" + groupe; else url += "/null";
            $('#loading').show();
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: callback,
                error: function () {
                    $('#list-analysis').hide();
                    $('#notfoundimg').show();
                },
                complete: function () {
                    $('#loading').hide();
                }
            });
        },

        /**
         * methode AJAX permettant de récuperer
         * les données d'un fichier d'analyse precis
         *
         * @param callback
         * @param name
         *          nom du fichier d'analyses
         */
        getAnalysisData : function(callback, name) {
            $.ajax( {
                url : "index.php/api/analysis/data/" + name,
                type : 'POST',
                dataType: 'json',
                success: callback,
                error: function(xhr, err) {
                    console.log(xhr);
                    console.log(err);
                },
                complete : function() {
                }
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

        /**
         * methode convertis les coordonnees
         * geographiques du format DMS vers format decimal
         *
         * @param input
         *          lat/lng sous format DMS
         * @returns {number}
         *          lat/lng sous format decimal
         */
        convertDMSToDD : function(input) {
            var parts = input.split(/[^\d\w\.]+/);
            var dd = Number(parts[0]) + Number(parts[1])/60 + Number(parts[2])/(60*60);

            if (parts[3] == "S" || parts[3] == "W") {
                dd = dd * -1;
            } // Don't do anything for N or E
            return dd;
        },

        /**
         * methode retourne le nom de fichier
         * simple du chemin entre en parametre
         *
         * @param path
         *          chemin d'ou tirer le basename
         * @returns {T}
         *          basename du chemin
         */
        baseName : function(path) {
            return path.split(/[\\/]/).pop();
        },

        /**
         * méthode permettant de telecharger le fichier
         * xlsx correspondant à l'analyse selectionnee
         */
        downloadXLSX : function() {
            var champ = $('#list-analysis').find('.active');
            if(champ !== null) {
                var fileName = champ.text();
                if(fileName != "") {
                    var link = document.createElement("a");
                    var uri = 'src/geoOTELo/scripts/download.php?file_name=' + fileName;
                    link.href = uri;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    delete link;
                }
            }
        }
    }

})();

window.onload = (function () {
    APP.modules.map.init('map');
    APP.init();
})();