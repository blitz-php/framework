/*
 * Fonctionnalité de la barre d'outils de débogage BlitzPHP.
 */

var blitzphpDebugBar = {
    toolbarContainer: null,
    toolbar: null,
    icon: null,

    init: function () {
        this.toolbarContainer = document.getElementById("toolbarContainer");
        this.toolbar = document.getElementById("debug-bar");
        this.icon = document.getElementById("debug-icon");

        blitzphpDebugBar.createListeners();
        blitzphpDebugBar.setToolbarState();
        blitzphpDebugBar.setToolbarPosition();
        blitzphpDebugBar.setToolbarTheme();
        blitzphpDebugBar.toggleViewsHints();
        blitzphpDebugBar.routerLink();
        blitzphpDebugBar.setHotReloadState();

        document
            .getElementById("debug-bar-link")
            .addEventListener("click", blitzphpDebugBar.toggleToolbar, true);
        document
            .getElementById("debug-icon-link")
            .addEventListener("click", blitzphpDebugBar.toggleToolbar, true);

        // Permet de mettre en évidence la ligne d'historique de la requete en cours
        var btn = this.toolbar.querySelector(
            'button[data-time="' + localStorage.getItem("debugbar-time") + '"]'
        );
        blitzphpDebugBar.addClass(btn.parentNode.parentNode, "current");

        historyLoad = this.toolbar.getElementsByClassName("blitzphp-history-load");

        for (var i = 0; i < historyLoad.length; i++) {
            historyLoad[i].addEventListener(
                "click",
                function () {
                    loadDoc(this.getAttribute("data-time"));
                },
                true
            );
        }

        // Afficher l'onglet actif au chargement de la page
        var tab = blitzphpDebugBar.readCookie("debug-bar-tab");
        if (document.getElementById(tab)) {
            var el = document.getElementById(tab);
            blitzphpDebugBar.switchClass(el, "debug-bar-ndisplay", "debug-bar-dblock");
            blitzphpDebugBar.addClass(el, "active");
            tab = document.querySelector("[data-tab=" + tab + "]");
            if (tab) {
                blitzphpDebugBar.addClass(tab.parentNode, "active");
            }
        }
    },

    createListeners: function () {
        var buttons = [].slice.call(
            this.toolbar.querySelectorAll(".blitzphp-label a")
        );

        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener("click", blitzphpDebugBar.showTab, true);
        }

        // Connecter une bascule générique via les attributs de données `data-toggle="foo"`
        var links = this.toolbar.querySelectorAll("[data-toggle]");
        for (var i = 0; i < links.length; i++) {
            let toggleData = links[i].getAttribute("data-toggle");
            if (toggleData === "datatable") {

                let datatable = links[i].getAttribute("data-table");
                links[i].addEventListener("click", function() {
                    blitzphpDebugBar.toggleDataTable(datatable)
                }, true);
               
            } else if (toggleData === "childrows") {

                let child = links[i].getAttribute("data-child");
                links[i].addEventListener("click", function() {
                    blitzphpDebugBar.toggleChildRows(child)
                }, true);
                
            } else {
                links[i].addEventListener("click", blitzphpDebugBar.toggleRows, true);
            }
        }
    },

    showTab: function () {
        // Obtenir l'onglet cible, le cas échéant
        var tab = document.getElementById(this.getAttribute("data-tab"));

        // Si l'étiquette n'a pas de tabulation, arrêtez-vous ici
        if (! tab) {
            return;
        }

        // Supprimer le cookie de la barre de débogage
        blitzphpDebugBar.createCookie("debug-bar-tab", "", -1);

        // Vérifiez notre état actuel.
        var state = tab.classList.contains("debug-bar-dblock");

        // Masquer tous les onglets
        var tabs = document.querySelectorAll("#debug-bar .tab");

        for (var i = 0; i < tabs.length; i++) {
            blitzphpDebugBar.switchClass(tabs[i], "debug-bar-dblock", "debug-bar-ndisplay");
        }

        // Marquer toutes les étiquettes comme inactives
        var labels = document.querySelectorAll("#debug-bar .blitzphp-label");

        for (var i = 0; i < labels.length; i++) {
            blitzphpDebugBar.removeClass(labels[i], "active");
        }

        // Afficher/masquer l'onglet sélectionné
        if (! state) {
            blitzphpDebugBar.switchClass(tab, "debug-bar-ndisplay", "debug-bar-dblock");
            blitzphpDebugBar.addClass(this.parentNode, "active");
            // Créer un cookie de débogage-barre-onglet à l'état persistant
            blitzphpDebugBar.createCookie(
                "debug-bar-tab",
                this.getAttribute("data-tab"),
                365
            );
        }
    },

    addClass: function (el, className) {
        if (el.classList) {
            el.classList.add(className);
        } else {
            el.className += " " + className;
        }
    },

    removeClass: function (el, className) {
        if (el.classList) {
            el.classList.remove(className);
        } else {
            el.className = el.className.replace(
                new RegExp(
                    "(^|\\b)" + className.split(" ").join("|") + "(\\b|$)",
                    "gi"
                ),
                " "
            );
        }
    },

    switchClass  : function(el, classFrom, classTo) {
        blitzphpDebugBar.removeClass(el, classFrom);
        blitzphpDebugBar.addClass(el, classTo);
    },

    /**
     * Basculer l'affichage d'un autre objet en fonction de la valeur de basculement des données de cet objet
     *
     * @param event
     */
    toggleRows: function (event) {
        if (event.target) {
            let row = event.target.closest("tr");
            let target = document.getElementById(
                row.getAttribute("data-toggle")
            );

            if (target.classList.contains("debug-bar-ndisplay")) {
                blitzphpDebugBar.switchClass(target, "debug-bar-ndisplay", "debug-bar-dtableRow");   
            } else {
                blitzphpDebugBar.switchClass(target, "debug-bar-dtableRow", "debug-bar-ndisplay");
            } 
        }
    },

    /**
     * Basculer l'affichage d'un tableau de données
     *
     * @param obj
     */
    toggleDataTable: function (obj) {
        if (typeof obj == "string") {
            obj = document.getElementById(obj + "_table");
        }

        if (obj) {
            if (obj.classList.contains("debug-bar-ndisplay")) {
                blitzphpDebugBar.switchClass(obj, "debug-bar-ndisplay", "debug-bar-dblock");
            } else {
                blitzphpDebugBar.switchClass(obj, "debug-bar-dblock", "debug-bar-ndisplay");
            }
        }
    },

    /**
     * Activer/désactiver l'affichage des éléments enfants de la chronologie
     *
     * @param obj
     */
    toggleChildRows: function (obj) {
        if (typeof obj == "string") {
            par = document.getElementById(obj + "_parent");
            obj = document.getElementById(obj + "_children");
        }

        if (par && obj) {

            if (obj.classList.contains("debug-bar-ndisplay")) {
                blitzphpDebugBar.removeClass(obj, "debug-bar-ndisplay");
            } else {
                blitzphpDebugBar.addClass(obj, "debug-bar-ndisplay");
            }

            par.classList.toggle("timeline-parent-open");
        }
    },

    //--------------------------------------------------------------------

    /**
     *   Basculer la barre d'outils de pleine à icône et de l'icône à pleine (aggrandir/reduire)
     */
    toggleToolbar: function () {
        var open = ! blitzphpDebugBar.toolbar.classList.contains("debug-bar-ndisplay");

        if (open) {
            blitzphpDebugBar.switchClass(blitzphpDebugBar.icon, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
            blitzphpDebugBar.switchClass(blitzphpDebugBar.toolbar, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
        } else {
            blitzphpDebugBar.switchClass(blitzphpDebugBar.icon, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
            blitzphpDebugBar.switchClass(blitzphpDebugBar.toolbar, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
        }

        // Remember it for other page loads on this site
        blitzphpDebugBar.createCookie("debug-bar-state", "", -1);
        blitzphpDebugBar.createCookie(
            "debug-bar-state",
            open == true ? "minimized" : "open",
            365
        );
    },

    /**
     * Définit l'état initial de la barre d'outils (ouverte ou réduite) lorsque la page est chargée 
     * pour la première fois pour lui permettre de mémoriser l'état entre les actualisations.
     */
    setToolbarState: function () {
        var open = blitzphpDebugBar.readCookie("debug-bar-state");

        if (open != "open") {
            blitzphpDebugBar.switchClass(blitzphpDebugBar.icon, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
            blitzphpDebugBar.switchClass(blitzphpDebugBar.toolbar, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
        } else {
            blitzphpDebugBar.switchClass(blitzphpDebugBar.icon, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
            blitzphpDebugBar.switchClass(blitzphpDebugBar.toolbar, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
        } 
    },

    toggleViewsHints: function () {
        // Évitez les astuces de basculement sur les demandes d'historique qui ne sont pas les initiales
        if (
            localStorage.getItem("debugbar-time") !=
            localStorage.getItem("debugbar-time-new")
        ) {
            var a = document.querySelector('a[data-tab="blitzphp-views"]');
            a.href = "#";
            return;
        }

        var nodeList = []; // [ Element, NewElement( 1 )/OldElement( 0 ) ]
        var sortedComments = [];
        var comments = [];

        var getComments = function () {
            var nodes = [];
            var result = [];
            var xpathResults = document.evaluate(
                "//comment()[starts-with(., ' DEBUG-VIEW')]",
                document,
                null,
                XPathResult.ANY_TYPE,
                null
            );
            var nextNode = xpathResults.iterateNext();
            while (nextNode) {
                nodes.push(nextNode);
                nextNode = xpathResults.iterateNext();
            }

            // trier les commentaires par balises d'ouverture et de fermeture
            for (var i = 0; i < nodes.length; ++i) {
                // obtenir le chemin du fichier + le nom à utiliser comme clé
                var path = nodes[i].nodeValue.substring(
                    18,
                    nodes[i].nodeValue.length - 1
                );

                if (nodes[i].nodeValue[12] === "S") {
                    // vérification simple pour démarrer le commentaire
                    // créer une nouvelle entrée
                    result[path] = [nodes[i], null];
                } else if (result[path]) {
                    // ajouter à l'entrée existante
                    result[path][1] = nodes[i];
                }
            }

            return result;
        };

        // trouver le nœud qui a TargetNode comme parentNode
        var getParentNode = function (node, targetNode) {
            if (node.parentNode === null) {
                return null;
            }

            if (node.parentNode !== targetNode) {
                return getParentNode(node.parentNode, targetNode);
            }

            return node;
        };

        // définir les éléments invalides et externes (également invalides)
        const INVALID_ELEMENTS = ["NOSCRIPT", "SCRIPT", "STYLE"];
        const OUTER_ELEMENTS = ["HTML", "BODY", "HEAD"];

        var getValidElementInner = function (node, reverse) {
            // gérer les balises invalides
            if (OUTER_ELEMENTS.indexOf(node.nodeName) !== -1) {
                for (var i = 0; i < document.body.children.length; ++i) {
                    var index = reverse
                        ? document.body.children.length - (i + 1)
                        : i;
                    var element = document.body.children[index];

                    // ignorer les balises invalides
                    if (INVALID_ELEMENTS.indexOf(element.nodeName) !== -1) {
                        continue;
                    }

                    return [element, reverse];
                }

                return null;
            }

            // passer à l'élément valide suivant
            while (
                node !== null &&
                INVALID_ELEMENTS.indexOf(node.nodeName) !== -1
            ) {
                node = reverse
                    ? node.previousElementSibling
                    : node.nextElementSibling;
            }

            // renvoyer un element non-tableau (null) si nous n'avons pas trouvé quelque chose
            if (node === null) {
                return null;
            }

            return [node, reverse];
        };

        // Obtenir l'élément valide suivant (pour ajouter des divs en toute sécurité)
        // @return [élément, élément ignoré] ou null si nous n'avons pas trouvé d'emplacement valide
        var getValidElement = function (nodeElement) {
            if (nodeElement) {
                if (nodeElement.nextElementSibling !== null) {
                    return (
                        getValidElementInner(
                            nodeElement.nextElementSibling,
                            false
                        ) ||
                        getValidElementInner(
                            nodeElement.previousElementSibling,
                            true
                        )
                    );
                }
                if (nodeElement.previousElementSibling !== null) {
                    return getValidElementInner(
                        nodeElement.previousElementSibling,
                        true
                    );
                }
            }

            // quelque chose s'est mal passé ! -> l'élément n'est pas dans le DOM
            return null;
        };

        function showHints() {
            // Vous aviez AJAX ? Réinitialiser les blocs de vue
            sortedComments = getComments();

            for (var key in sortedComments) {
                var startElement = getValidElement(sortedComments[key][0]);
                var endElement = getValidElement(sortedComments[key][1]);

                // ignorer si nous ne pouvons pas obtenir un élément valide
                if (startElement === null || endElement === null) {
                    continue;
                }

                // trouver l'élément qui a le même parent que l'élément de départ
                var jointParent = getParentNode(
                    endElement[0],
                    startElement[0].parentNode
                );
                if (jointParent === null) {
                    // trouver l'élément qui a le même parent que l'élément final
                    jointParent = getParentNode(
                        startElement[0],
                        endElement[0].parentNode
                    );
                    if (jointParent === null) {
                        // les deux tentatives ont échoué
                        continue;
                    } else {
                        startElement[0] = jointParent;
                    }
                } else {
                    endElement[0] = jointParent;
                }

                var debugDiv = document.createElement("div"); // titulaire
                var debugPath = document.createElement("div"); // chemein
                var childArray = startElement[0].parentNode.childNodes; // tableau des enfants cibles
                var parent = startElement[0].parentNode;
                var start, end;

                // configuration du container
                debugDiv.classList.add("debug-view");
                debugDiv.classList.add("show-view");
                debugPath.classList.add("debug-view-path");
                debugPath.innerText = key;
                debugDiv.appendChild(debugPath);

                // calculer la distance entre eux
                // debut
                for (var i = 0; i < childArray.length; ++i) {
                    // vérifier le commentaire (début et fin) -> s'il est antérieur à un élément de départ valide
                    if (
                        childArray[i] === sortedComments[key][1] ||
                        childArray[i] === sortedComments[key][0] ||
                        childArray[i] === startElement[0]
                    ) {
                        start = i;
                        if (childArray[i] === sortedComments[key][0]) {
                            start++; // augmenter pour ignorer le commentaire de départ
                        }
                        break;
                    }
                }
                // ajuster si nous voulons ignorer l'élément de départ
                if (startElement[1]) {
                    start++;
                }

                // fin
                for (var i = start; i < childArray.length; ++i) {
                    if (childArray[i] === endElement[0]) {
                        end = i;
                        // ne pas interrompre pour vérifier le commentaire de fin après l'élément valide de fin
                    } else if (childArray[i] === sortedComments[key][1]) {
                        // si nous trouvons le commentaire final, nous pouvons casser
                        end = i;
                        break;
                    }
                }

                // déplacer des éléments
                var number = end - start;
                if (endElement[1]) {
                    number++;
                }
                for (var i = 0; i < number; ++i) {
                    if (INVALID_ELEMENTS.indexOf(childArray[start]) !== -1) {
                        // ignorer les enfants invalides qui peuvent causer des problèmes s'ils sont déplacés
                        start++;
                        continue;
                    }
                    debugDiv.appendChild(childArray[start]);
                }

                // ajouter le conteneur au DOM
                nodeList.push(parent.insertBefore(debugDiv, childArray[start]));
            }

            blitzphpDebugBar.createCookie("debug-view", "show", 365);
            blitzphpDebugBar.addClass(btn, "active");
        }

        function hideHints() {
            for (var i = 0; i < nodeList.length; ++i) {
                var index;

                // trouver l'index
                for (
                    var j = 0;
                    j < nodeList[i].parentNode.childNodes.length;
                    ++j
                ) {
                    if (nodeList[i].parentNode.childNodes[j] === nodeList[i]) {
                        index = j;
                        break;
                    }
                }

                // déplacer l'enfant vers l'arrière
                while (nodeList[i].childNodes.length !== 1) {
                    nodeList[i].parentNode.insertBefore(
                        nodeList[i].childNodes[1],
                        nodeList[i].parentNode.childNodes[index].nextSibling
                    );
                    index++;
                }

                nodeList[i].parentNode.removeChild(nodeList[i]);
            }
            nodeList.length = 0;

            blitzphpDebugBar.createCookie("debug-view", "", -1);
            blitzphpDebugBar.removeClass(btn, "active");
        }

        var btn = document.querySelector("[data-tab=blitzphp-views]");

        // Si le collecteur de vues est inactif, il s'arrête ici
        if (! btn) {
            return;
        }

        btn.parentNode.onclick = function () {
            if (blitzphpDebugBar.readCookie("debug-view")) {
                hideHints();
            } else {
                showHints();
            }
        };

        // Déterminer l'état des indices lors du chargement de la page
        if (blitzphpDebugBar.readCookie("debug-view")) {
            showHints();
        }
    },

    setToolbarPosition: function () {
        var btnPosition = this.toolbar.querySelector("#toolbar-position");

        if (blitzphpDebugBar.readCookie("debug-bar-position") === "top") {
            blitzphpDebugBar.addClass(blitzphpDebugBar.icon, "fixed-top");
            blitzphpDebugBar.addClass(blitzphpDebugBar.toolbar, "fixed-top");
        }

        btnPosition.addEventListener(
            "click",
            function () {
                var position = blitzphpDebugBar.readCookie("debug-bar-position");

                blitzphpDebugBar.createCookie("debug-bar-position", "", -1);

                if (! position || position === "bottom") {
                    blitzphpDebugBar.createCookie("debug-bar-position", "top", 365);
                    blitzphpDebugBar.addClass(blitzphpDebugBar.icon, "fixed-top");
                    blitzphpDebugBar.addClass(blitzphpDebugBar.toolbar, "fixed-top");
                } else {
                    blitzphpDebugBar.createCookie(
                        "debug-bar-position",
                        "bottom",
                        365
                    );
                    blitzphpDebugBar.removeClass(blitzphpDebugBar.icon, "fixed-top");
                    blitzphpDebugBar.removeClass(blitzphpDebugBar.toolbar, "fixed-top");
                }
            },
            true
        );
    },

    setToolbarTheme: function () {
        var btnTheme = this.toolbar.querySelector("#toolbar-theme");
        var isDarkMode = window.matchMedia(
            "(prefers-color-scheme: dark)"
        ).matches;
        var isLightMode = window.matchMedia(
            "(prefers-color-scheme: light)"
        ).matches;

        // Si un cookie est défini avec une valeur, nous forçons le schéma de couleurs
        if (blitzphpDebugBar.readCookie("debug-bar-theme") === "dark") {
            blitzphpDebugBar.removeClass(blitzphpDebugBar.toolbarContainer, "light");
            blitzphpDebugBar.addClass(blitzphpDebugBar.toolbarContainer, "dark");
        } else if (blitzphpDebugBar.readCookie("debug-bar-theme") === "light") {
            blitzphpDebugBar.removeClass(blitzphpDebugBar.toolbarContainer, "dark");
            blitzphpDebugBar.addClass(blitzphpDebugBar.toolbarContainer, "light");
        }

        btnTheme.addEventListener(
            "click",
            function () {
                var theme = blitzphpDebugBar.readCookie("debug-bar-theme");

                if (
                    ! theme &&
                    window.matchMedia("(prefers-color-scheme: dark)").matches
                ) {
                    // S'il n'y a pas de cookie et que « prefers-color-scheme » est défini sur « dark », 
                    // cela signifie que l'utilisateur souhaite passer en mode clair.
                    blitzphpDebugBar.createCookie("debug-bar-theme", "light", 365);
                    blitzphpDebugBar.removeClass(blitzphpDebugBar.toolbarContainer, "dark");
                    blitzphpDebugBar.addClass(blitzphpDebugBar.toolbarContainer, "light");
                } else {
                    if (theme === "dark") {
                        blitzphpDebugBar.createCookie(
                            "debug-bar-theme",
                            "light",
                            365
                        );
                        blitzphpDebugBar.removeClass(
                            blitzphpDebugBar.toolbarContainer,
                            "dark"
                        );
                        blitzphpDebugBar.addClass(
                            blitzphpDebugBar.toolbarContainer,
                            "light"
                        );
                    } else {
                        // Dans tous les autres cas : s'il n'y a pas de cookie, ou si le cookie est réglé sur 
                        // « light », ou si le « prefers-color-scheme » est « light »...
                        blitzphpDebugBar.createCookie("debug-bar-theme", "dark", 365);
                        blitzphpDebugBar.removeClass(
                            blitzphpDebugBar.toolbarContainer,
                            "light"
                        );
                        blitzphpDebugBar.addClass(
                            blitzphpDebugBar.toolbarContainer,
                            "dark"
                        );
                    }
                }
            },
            true
        );
    },

    setHotReloadState: function () {
        var btn = document.getElementById("debug-hot-reload").parentNode;
        var btnImg = btn.getElementsByTagName("img")[0];
        var eventSource;

        // Si le collecteur de rechargement à chaud est inactif, il s'arrête ici
        if (! btn) {
            return;
        }

        btn.onclick = function () {
            if (blitzphpDebugBar.readCookie("debug-hot-reload")) {
                blitzphpDebugBar.createCookie("debug-hot-reload", "", -1);
                blitzphpDebugBar.removeClass(btn, "active");
                blitzphpDebugBar.removeClass(btnImg, "rotate");

                // Fermez la connexion EventSource si elle existe
                if (typeof eventSource !== "undefined") {
                    eventSource.close();
                    eventSource = void 0; // Indéfinir la variable
                }
            } else {
                blitzphpDebugBar.createCookie("debug-hot-reload", "show", 365);
                blitzphpDebugBar.addClass(btn, "active");
                blitzphpDebugBar.addClass(btnImg, "rotate");

                eventSource = blitzphpDebugBar.hotReloadConnect();
            }
        };

        // Déterminer l'état de rechargement à chaud lors du chargement de la page
        if (blitzphpDebugBar.readCookie("debug-hot-reload")) {
            blitzphpDebugBar.addClass(btn, "active");
            blitzphpDebugBar.addClass(btnImg, "rotate");
            eventSource = blitzphpDebugBar.hotReloadConnect();
        }
    },

    hotReloadConnect: function () {
        const eventSource = new EventSource(blitzSiteURL  + "/__hot-reload");

        eventSource.addEventListener("reload", function (e) {
            console.log("reload", e);
            window.location.reload();
        });

        eventSource.onerror = (err) => {
            console.error("EventSource failed:", err);
        };

        return eventSource;
    },

    /**
     * Aide à la création d'un cookie.
     *
     * @param name
     * @param value
     * @param days
     */
    createCookie: function (name, value, days) {
        if (days) {
            var date = new Date();

            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);

            var expires = "; expires=" + date.toGMTString();
        } else {
            var expires = "";
        }

        document.cookie =
            name + "=" + value + expires + "; path=/; samesite=Lax";
    },

    readCookie: function (name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(";");

        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == " ") {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) == 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    },

    trimSlash: function (text) {
        return text.replace(/^\/|\/$/g, "");
    },

    routerLink: function () {
        var row, _location;
        var rowGet = this.toolbar.querySelectorAll(
            'td[data-debugbar-route="GET"]'
        );
        var patt = /\((?:[^)(]+|\((?:[^)(]+|\([^)(]*\))*\))*\)/;

        for (var i = 0; i < rowGet.length; i++) {
            row = rowGet[i];
            if (!/\/\(.+?\)/.test(rowGet[i].innerText)) {
                blitzphpDebugBar.addClass(row, "debug-bar-pointer");
                row.setAttribute(
                    "title",
                    location.origin + "/" + blitzphpDebugBar.trimSlash(row.innerText)
                );
                row.addEventListener("click", function (ev) {
                    _location =
                        location.origin +
                        "/" +
                        blitzphpDebugBar.trimSlash(ev.target.innerText);
                    var redirectWindow = window.open(_location, "_blank");
                    redirectWindow.location;
                });
            } else {
                row.innerHTML =
                    "<div>" +
                    row.innerText +
                    "</div>" +
                    '<form data-debugbar-route-tpl="' +
                    blitzphpDebugBar.trimSlash(row.innerText.replace(patt, "?")) +
                    '">' +
                    row.innerText.replace(
                        patt,
                        '<input type="text" placeholder="$1">'
                    ) +
                    '<input type="submit" value="Go" class="debug-bar-mleft4">' +
                    "</form>";
            }
        }

        rowGet = this.toolbar.querySelectorAll(
            'td[data-debugbar-route="GET"] form'
        );
        for (var i = 0; i < rowGet.length; i++) {
            row = rowGet[i];

            row.addEventListener("submit", function (event) {
                event.preventDefault();
                var inputArray = [],
                    t = 0;
                var input = event.target.querySelectorAll("input[type=text]");
                var tpl = event.target.getAttribute("data-debugbar-route-tpl");

                for (var n = 0; n < input.length; n++) {
                    if (input[n].value.length > 0) {
                        inputArray.push(input[n].value);
                    }
                }

                if (inputArray.length > 0) {
                    _location =
                        location.origin +
                        "/" +
                        tpl.replace(/\?/g, function () {
                            return inputArray[t++];
                        });

                    var redirectWindow = window.open(_location, "_blank");
                    redirectWindow.location;
                }
            });
        }
    },
};
