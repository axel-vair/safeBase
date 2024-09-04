import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    collapse() {
        document.body.classList.toggle('sidebar-collapse')
    }

    collapseCard() {
        const cardBody = this.element.querySelector('.card-body'); // Sélectionne le corps de la carte
        const isCollapsed = this.element.classList.toggle('collapsed-card'); // Change la classe de la carte
        const cardFooter = this.element.querySelector('.card-footer');

        // Gère l'affichage du corps de la carte
        if (isCollapsed) {
            cardBody.style.display = 'none'; // Masque le corps de la carte
            cardFooter.style.display = 'none';

        } else {
            cardBody.style.display = 'block'; // Affiche le corps de la carte
            cardFooter.style.display = 'block';

        }
    }
}
