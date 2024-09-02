import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    collapse() {
        document.body.classList.toggle('sidebar-collapse')
    }

   /* openInjections() {
        const injections = document.getElementById('injections')
        injections.classList.toggle('menu-open')
    }

    openAdministration(){
        const administration = document.getElementById('administration')
        administration.classList.toggle('menu-open')

    }

    tab(event){
        const navLinks = this.element.querySelectorAll('.nav-link');
        const tabId = event.target.dataset.id;
        const informationsTableau = document.getElementById('informations-tableau');
        const abonnementsTableau = document.getElementById('abonnements-tableau');
        const verificationsTableau = document.getElementById('verifications-tableau');


        navLinks.forEach(link => {
            if (link.dataset.id === tabId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        if (tabId === 'informations') {
            informationsTableau.style.display = 'block';
        } else {
            informationsTableau.style.display = 'none';
        }

        if (tabId === 'abonnements') {
            abonnementsTableau.style.display = 'block';
        } else {
            abonnementsTableau.style.display = 'none';
        }

        if (tabId === 'verifications') {
            verificationsTableau.style.display = 'block';
        } else {
            verificationsTableau.style.display = 'none';
        }
    }

    tabVeille(event){
        const navLinks = this.element.querySelectorAll('.nav-link');
        const tabId = event.target.dataset.id;
        const informationsVeille = document.getElementById('informations-veille');
        const abonnesVeille = document.getElementById('abonnes-veille');
        const statistiquesVeille = document.getElementById('statistiques-veille');

        navLinks.forEach(link => {
            if (link.dataset.id === tabId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        if (tabId === 'informations-veille') {
            informationsVeille.style.display = 'block';
        } else {
            informationsVeille.style.display = 'none';
        }

        if (tabId === 'abonnes-veille') {
            abonnesVeille.style.display = 'block';
        } else {
            abonnesVeille.style.display = 'none';
        }

        if (tabId === 'statistiques-veille') {
            statistiquesVeille.style.display = 'block';
        } else {
            statistiquesVeille.style.display = 'none';
        }
    }
*/
}
