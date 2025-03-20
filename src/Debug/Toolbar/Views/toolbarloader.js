document.addEventListener('DOMContentLoaded', loadDoc, false);

function loadDoc(time) {
    if (isNaN(time)) {
        time = document.getElementById("debugbar_loader").getAttribute("data-time");
        localStorage.setItem('debugbar-time', time);
    }

    localStorage.setItem('debugbar-time-new', time);

    let url = '{url}';
    let xhttp = new XMLHttpRequest();

    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            let toolbar = document.getElementById("toolbarContainer");

            if (! toolbar) {
                toolbar = document.createElement('div');
                toolbar.setAttribute('id', 'toolbarContainer');
                document.body.appendChild(toolbar);
            }

            let responseText = this.responseText;
            let dynamicStyle = document.getElementById('debugbar_dynamic_style');
            let dynamicScript = document.getElementById('debugbar_dynamic_script');

            // récupérez le premier bloc de style, copiez le contenu dans dynamic_style, puis supprimez-le ici
            let start = responseText.indexOf('>', responseText.indexOf('<style')) + 1;
            let end = responseText.indexOf('</style>', start);
            dynamicStyle.innerHTML = responseText.substr(start, end - start);
            responseText = responseText.substr(end + 8);

            // récupérez le premier script après le premier style, copiez le contenu dans dynamic_script, puis supprimez-le ici
            start = responseText.indexOf('>', responseText.indexOf('<script')) + 1;
            end = responseText.indexOf('\<\/script>', start);
            dynamicScript.innerHTML = responseText.substr(start, end - start);
            responseText = responseText.substr(end + 9);

            // vérifier le dernier bloc de style, ajouter le contenu à dynamic_style, puis supprimer ici
            start = responseText.indexOf('>', responseText.indexOf('<style')) + 1;
            end = responseText.indexOf('</style>', start);
            dynamicStyle.innerHTML += responseText.substr(start, end - start);
            responseText = responseText.substr(0, start - 8);

            toolbar.innerHTML = responseText;

            if (typeof blitzphpDebugBar === 'object') {
                blitzphpDebugBar.init();
            }
        } else if (this.readyState === 4 && this.status === 404) {
            console.log('BlitzPHP DebugBar: File "STORAGE_PATH/debugbar/debugbar_' + time + '" not found.');
        }
    };

    xhttp.open("GET", url + "?debugbar_time=" + time, true);
    xhttp.send();
}

window.oldXHR = window.ActiveXObject
    ? new ActiveXObject('Microsoft.XMLHTTP')
    : window.XMLHttpRequest;

function newXHR() {
    const realXHR = new window.oldXHR();

    realXHR.addEventListener("readystatechange", function() {
        // Only success responses and URLs that do not contains "debugbar_time" are tracked
        if (realXHR.readyState === 4 && realXHR.status.toString()[0] === '2' && realXHR.responseURL.indexOf('debugbar_time') === -1) {
            if (realXHR.getAllResponseHeaders().indexOf("Debugbar-Time") >= 0) {
                let debugbarTime = realXHR.getResponseHeader('Debugbar-Time');

                if (debugbarTime) {
                    let h2 = document.querySelector('#blitzphp-history > h2');

                    if (h2) {
                        h2.innerHTML = 'Historique <small>Vous avez de nouvelles données de débogage.</small> <button id="blitzphp-history-update">Mettre à jour</button>';
                        document.querySelector('a[data-tab="blitzphp-history"] > span > .badge').className += ' active';
                        document.getElementById('blitzphp-history-update').addEventListener('click', function () {
                            loadDoc(debugbarTime);
                        }, false)
                    }
                }
            }
        }
    }, false);
    return realXHR;
}

window.XMLHttpRequest = newXHR;
