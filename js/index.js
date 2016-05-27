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
            APP.modules.service.getStations(APP.modules.map.affichageStations, "all", "none");
            APP.modules.service.getTypes(APP.modules.affichage.initTypeComboboxes);
            $('#filterButton').click(APP.modules.affichage.showFilterMenu);
            $('#refreshButton').click(APP.modules.map.refresh);
            $('.panel-heading').click(APP.modules.affichage.toggleElement);
            $('#refreshButton2').click(APP.modules.affichage.showAnalysis);
            $(document).on('click', '.list-group-item', APP.modules.affichage.selectAnalysis);
            $('#download').click(APP.modules.utility.downloadXLSX);
            $('.filtersSelect').on('change', APP.modules.affichage.showAnalysis);
            var typeCombobox = $('#typeCombobox');
            typeCombobox.on('change', APP.modules.affichage.initSpecificMeasurementCombobox);
            $('.filterStation').on('change', APP.modules.map.refresh);
            var typeFilterAnalysisCombobox = $('#typeFilterAnalysisCombobox');
            typeFilterAnalysisCombobox.on('change', APP.modules.affichage.initFilterGroupAnalysisCombobox);
            typeFilterAnalysisCombobox.on('change', APP.modules.affichage.initSpecificMeasurementCombobox);
            $('#openButton').click(APP.modules.affichage.showModal);
            var dateInterval = $('#dateInterval');
            dateInterval.daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                },
                "showDropdowns": true,
                "autoApply": true,
                "startDate": moment('2013-01-01'),
                "endDate": moment()
            }, function(start, end, label) {
            });
            dateInterval.on('apply.daterangepicker', APP.modules.affichage.showAnalysis);
            $('#default').click(APP.modules.affichage.reinitDateInterval);
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
            var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            var ign = new L.TileLayer.WMTS("https://wxs.ign.fr/9bci2kf4ow18mxkruzqcl3pi/geoportail/wmts",
                {
                    layer: "ORTHOIMAGERY.ORTHOPHOTOS",
                    style: "normal",
                    tilematrixSet: "PM",
                    format: "image/jpeg",
                    attribution: "<a href='http://www.ign.fr'>IGN</a>"
                }
            );
            var baseLayers = {"IGN" : ign, "OpenStreetMap" : osm};
            L.control.scale({'position':'bottomleft','metric':true,'imperial':false}).addTo(map);
            L.control.layers(baseLayers, {}).addTo(map);
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
                var long = k.LONGITUDE.replace(/\s+/g, '');
                var lat = k.LATITUDE.replace(/\s+/g, '');
                var firstProj = '+proj=lcc +lat_1=49 +lat_2=44 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';
                var secondProj = '+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs ';
                var latlng = proj4(firstProj, secondProj, [k.LONGITUDE, k.LATITUDE]);
                var newMarker = new DataMarker([latlng[1], latlng[0]], {icon: stationIcon}).bindLabel(k.ABBREVIATION);
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
            lastTypeCombobox = $('#typeCombobox');
            var specificMeasurement = $('#measurementCombobox').val();
            if(lastTypeCombobox != null && specificMeasurement != null) {
                var type = lastTypeCombobox.val();
                APP.modules.map.clearMarkers();
                APP.modules.service.getStations(APP.modules.map.affichageStations, type, specificMeasurement);
            }
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
    var specificMeasureCombobox = $('#specificMeasurementCombobox');
    var openPanelButton = $('#openInformation');
    var dateDebut, dateFin;

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
            var groupAnalysis = {"PSD" : "Particle Size Distribution", "MIN" : "Mineralogy", "EA" : "Element Analysis", "PAC" : "Polycyclic Aromatic Compounds", "MIC" : "Microbiology", "XRF" : "X-Ray Fluorescence", "GP" : "Global Parameters", "ISO" : "Isotopic", "DMT" : "Donnan Membrane Technique", "16S-MGE" : "", "ECOLI-ENT" : "", "PHAGE" : "", "QMJ" : "Daily Integrated Flow", "QTVAR" : "Instantaneous Flow", "CAMPY-VIRO" : "", "MET-HAP" : ""};
            switch(typeFilterAnalysisCombobox.val()) {
                case "all" :
                    break;
                case "sediment" :
                    groupAnalysis = {"GP" : "Global Parameters", "EA" : "Element Analysis", "PSD" : "Particle Size Distribution", "XRF" : "X-Ray Fluorescence", "PAC" : "Polyclic Aromatic Compounds"};
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
            groupMeasuresCombobox.append($('<option>', {
                value: "all",
                text : "all"
            }));
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
         * méthode permettant d'initialiser
         */
        initSpecificMeasurementCombobox : function(e) {
            var target = $(e.target);
            var combobox = specificMeasureCombobox;
            if(target.is("#typeCombobox")) {
                combobox = $('#measurementCombobox');
            }
            combobox.empty();
            var measurements = null;
            switch(target.val()) {
                case "all" :
                    break;
                case "sediment" :
                    break;
                case "hydrology" :
                    break;
                case "spm" :
                    break;
                case "water" :
                    measurements = {"TSS" : "Total Suspended Solid concentration", "turb" : "turbidity"};
                    break;
            }
            combobox.append($('<option>', {
                value: 'none',
                text: 'none'
            }));
            for (var index in measurements) {
                combobox.append($('<option>', {
                    value: index,
                    html: index + " : <i>" + measurements[index] + "</i>"
                }));
            }
            if(measurements != null) {
                combobox.attr('disabled', false);
            }
            combobox.selectpicker('refresh');
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
                var coord = $("#coord");
                if(informationDiv.is(":hidden")) {
                    titre.text("Station : " + station.ABBREVIATION);
                    description.text(station.DESCRIPTION);
                    nomStation.text(station.NAME);
                    coord.text("latitude : " + latlng.lat + " ; longitude : " + latlng.lng);
                    informationDiv.toggle("slide", {direction : 'right'});
                } else if(titre.text() !== ("Station : " + station.ABBREVIATION)) {
                    informationDiv.toggle("slide", {direction : 'right'});
                    titre.text("Station : " + station.ABBREVIATION);
                    description.text(station.DESCRIPTION);
                    nomStation.text(station.NAME);
                    coord.text("latitude : " + latlng.lat + " ; longitude : " + latlng.lng);
                    informationDiv.toggle("slide", {direction : 'right'});
                }
                if($('#stationInfosBody').is(":hidden")) {
                    titre.trigger("click");
                }
                if($('#analysesBody').is(":visible")) analysesDiv.trigger("click");
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
                 $('#refreshButton2').trigger("click");
            }
            var analysis = $("#analysesBody");
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
            var specificMeasurement = specificMeasureCombobox.val();
            var dateInterval = $('#dateInterval').data('daterangepicker');
            dateDebut = dateInterval.startDate._d;
            dateFin = dateInterval.endDate._d;
            if(type === "all") type = null;
            if(groupeMesure === "all") groupeMesure = null;
            if(specificMeasurement === "none" || specificMeasurement == "") specificMeasurement = null;
            if(currentSetting != lastSetting) {
                APP.modules.service.getAnalysisNames(APP.modules.affichage.showAnalysisField, station, type, groupeMesure, specificMeasurement);
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
                if(dateDebut != null && dateFin != null) {
                    var dateComprise = false;
                    k.INTRO['SAMPLING DATE'].forEach(function(c, d) {
                        var split = c.split('-');
                        var currentDate = new Date(split[0], split[1] - 1, split[2]);
                        if(currentDate >= dateDebut && currentDate <= dateFin) {
                            dateComprise = true;
                        }
                    });
                    if(dateComprise) {
                        listAnalysis.append($('<li>', {
                            value: k._id,
                            text: k._id,
                            class: 'list-group-item'
                        }));
                    }
                } else {
                    listAnalysis.append($('<li>', {
                        value: k._id,
                        text: k._id,
                        class: 'list-group-item'
                    }));
                }
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
                        dataTable.append($('<tr>'));
                    }
                    dataTable.append($('<tr>'));
                    for(var property in k) {
                        if(i==0) {
                            dataTable.find("tr:first").append($('<th>', {
                                text: property,
                                class : 'info'
                            }));
                            var unit = null;
                            try {
                                data.MEASUREMENT.forEach(function (k, v) {
                                    if (k.NATURE == property) {
                                        unit = k.UNIT;
                                        throw new Exception("break forEach");
                                    }
                                });
                            } catch(e) {}
                            if(unit == null && property != 'station' && property != 'sample kind' && property != 'date' && property != 'hour' && property != "Sampling station") {
                                unit = "not specified";
                            }
                            dataTable.find("tr:eq(1)").append($('<td>', {
                                text: unit,
                                class : 'info'
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
        },

        /**
         * permet de réinitialiser le choix de l'interval de date
         */
        reinitDateInterval : function() {
            var dateInterval = $('#dateInterval').data('daterangepicker');
            dateInterval.setStartDate(moment('2013-01-01'));
            dateInterval.setEndDate(moment());
            $("#refreshButton2").trigger('click');
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
         * @param specificMeasurement
         *          mesure spécifique
         */
        getStations : function(callback, type, specificMeasurement) {
            var addUrl = "/";
            if(type == "all") {
                addUrl += "null";
            } else {
                addUrl = "/" + type;
            }
            if(specificMeasurement == "none") {
                addUrl+= "/none";
            } else {
                addUrl+= "/" + specificMeasurement;
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
                    $('body').notify({
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
        getAnalysisNames : function(callback, station, type, groupe, specificMeasurement) {
            var url = "index.php/api/analysis/intro/" + station;
            if (type) url += "/" + type; else url += "/null";
            if (groupe) url += "/" + groupe; else url += "/null";
            if(specificMeasurement) url += "/" + specificMeasurement; else url += "/null";
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