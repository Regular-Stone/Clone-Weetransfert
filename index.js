document.addEventListener('DOMContentLoaded', () => {
    const spans = document.querySelectorAll('.backgroundText span');

    spans.forEach((span, index) => {
        setTimeout(() => {
            span.classList.add('active');
        }, (index + 1) * 250); // L'ajustement du délai d'animation pour chaque span
    });
});

window.addEventListener('load', () => {
    main();
});

/**
 * default App, invoked in event load
 */
function main() {
    /**
     * Déclarations des variables
     */
    const range = document.createRange();
    const sendBtn = document.querySelector("#send");
    const emailDestinataireDom = document.querySelector('#destEmail');
    const emailExpediteurDom = document.querySelector('#expediteurEmail');
    const eMailListDom = document.querySelector('.email-list');
    const fichierDom = document.querySelector("#fichier");
    const form = document.querySelector('form');
    // Défini l'URL et le formulaire
    const url = `${location.origin}/src/upload.php`;
    const testUrl = `${location.origin}/blobupload.php`;

    /**
     * Evènements
     */
    form.addEventListener('change', (event) => {
        event.preventDefault();

        if (event.target.type === 'file') {
            updateFileName();
        }

        if (event.target.id === emailDestinataireDom.id) {
            if (isEmailValid(event.target.value)) {
                eMailListUpdate(event);
            } else {
                event.target.reportValidity();
            }
        }

        enableSendBtn();
    });

    form.addEventListener('click', async (event) => {
        if (event.target.type === 'submit') { return };

        if (event.target.className === 'email-del') {
            event.target.parentNode.remove();
            eMailCountDomUpdate();
            enableSendBtn();
            return;
        }
    });

    // `Enter` event add email to list
    emailDestinataireDom.addEventListener('keydown', (event) => {
        const eventCode = event.code === 'Enter' || event.code === 'NumpadEnter';
        if (eventCode && isEmailValid(emailDestinataireDom.value)) {
            event.preventDefault();
            eMailListUpdate(event);
        } else if (eventCode) {
            event.preventDefault();
            emailDestinataireDom.reportValidity();
        }

        enableSendBtn();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        // Déclaration des variables    
        const destEmail = [...eMailListDom.childNodes].map((value) => {
            return [...value.childNodes][0].textContent;
        });
        const files = fichierDom.files;
        const formData = new FormData();

        formData.append('destEmail', destEmail);
        formData.append('expediteurEmail', emailExpediteurDom.value);

        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            getFileMD5(file).then(md5 => {
                console.log(`MD5 du fichier ${file.name}: ${md5}`);
                uploadBlob(file, null, generateUID(), testUrl, md5);
            }).catch(error => {
                console.error(`Erreur lors du calcul du MD5 pour le fichier ${file.name}:`, error);
            });
        }

        resetForm();
        displaySpinner();
        isEmptyFile();

        // Envoie les données au serveur
        await fetch(url, {
            method: 'POST',
            body: formData,
        }).then((response) => {
            console.log(response)
        });

        return;
    });

    // Vérifie si l'email est correct
    const isEmailValid = (email) => {
        return email.toLowerCase().match(
            /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        );
    }

    function isEmptyFile() {
        const file = document.getElementById('fichier');

        if (file.files.length == 0) {
            $('#modalEmptyFile1').modal('show');
        }
    }

    function updateFileName() {
        const input = document.getElementById('fichier');
        const fileLabel = document.getElementById('fileNameLabel');
        const files = input.files;

        if (files.length === 0) {
            fileLabel.textContent = 'Choisir des fichiers';
        } else if (files.length === 1) {
            fileLabel.textContent = files[0].name;
        } else {
            const fileNames = Array.from(files).map(file => file.name).join(', ');
            fileLabel.textContent = fileNames;
        }
    }

    // update list email
    function eMailListUpdate(event) {
        const stringHtml = `<div class="email-wrap"><div>${event.target.value}</div><div class="email-del">❌</div></div>`;
        eMailListDom.appendChild(range.createContextualFragment(stringHtml));

        eMailCountDomUpdate();
        emailDestinataireDom.value = '';
    }

    // update eMailCountDom
    function eMailCountDomUpdate() {
        document.querySelector('.email-count').parentElement.innerHTML = eMailListDom.childNodes.length > 1 ?
            `Email destinataires: <span class="email-count">${eMailListDom.childNodes.length}</span>` :
            `Email destinataire: <span class="email-count">${eMailListDom.childNodes.length}</span>`;
    }

    // Reset le formulaire
    function resetForm() {
        emailDestinataireDom.value = "";
        emailExpediteurDom.value = "";
        document.getElementById('fileNameLabel').textContent = "Choisir des fichiers";
        eMailListDom.replaceChildren();
        eMailCountDomUpdate();
    }

    // Enable btn send
    function enableSendBtn() {
        if (fichierDom.files[0] && emailExpediteurDom.value && eMailListDom.childNodes.length > 0) {
            sendBtn.disabled = false;
        } else {
            sendBtn.disabled = true;
        }
    }

    // Affiche un spinner
    function displaySpinner() {
        const spinner = document.getElementById("spin");

        spinner.hidden = false;

        setTimeout(() => {
            spinner.hidden = true;
        }, 5000);
    }
}

async function getFileMD5(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            const fileContent = event.target.result;
            const wordArray = CryptoJS.lib.WordArray.create(fileContent);
            const hash = CryptoJS.MD5(wordArray).toString();
            resolve(hash);
        };
        reader.onerror = (error) => {
            reject(error);
        };
        reader.readAsArrayBuffer(file);
    });
}

async function uploadBlob(file, progressBar, uploadId, url, md5) {
    const chunkSize = 500_000;
    const totalChunks = Math.ceil(file.size / chunkSize);
    for (let i = 0, start = 0; start < file.size; start += chunkSize, i++) {
        let end = start + chunkSize;
        const chunk = file.slice(start, end);
        try {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    'Content-Disposition': `attachment; filename="${file.name}"`,
                    'Upload-ID': uploadId, // ID unique pour chaque upload
                    'Total-Chunks': totalChunks, // Nombre total de chunks
                    'File-MD5': md5 // MD5 du fichier complet
                },
                body: chunk
            });
            if (response.status !== 200) {
                throw new Error('Erreur lors de l\'upload');
            }
            if (progressBar) {
                progressBar.value = ((i + 1) / totalChunks) * 100;
            }
        } catch (error) {
            if (progressBar) {
                progressBar.textContent = 'Erreur lors de l\'upload : ' + error.message;
            }
            break;
        }
    }
    // Nous avons fini d'envoyer les chunks, on finalise l'upload
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                'Content-Disposition': `attachment; filename="${file.name}"`,
                'Upload-ID': uploadId,
                'Upload-Complete': 'true',
                'File-MD5': md5 // MD5 du fichier complet
            }
        });
        if (response.status !== 200) {
            throw new Error('Erreur lors de la finalisation de l\'upload');
        }
        if (progressBar) {
            progressBar.value = 100;
        }
    } catch (error) {
        if (progressBar) {
            progressBar.textContent = 'Erreur lors de la finalisation de l\'upload : ' + error.message;
        }
    }
}

function generateUID() {
    // Création d'un id unique pour chaque upload avec un timestamp et un nombre aléatoire
    return Date.now() + '-' + Math.random().toString(36).substr(2, 9);
}
