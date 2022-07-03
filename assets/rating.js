import $ from "jquery";

import './styles/rating.css';
import 'bootstrap-star-rating/js/star-rating';




$(document).ready(() => {
    const $rateVisit = $('#rate-visit');
    $rateVisit.rating({
        min:0, max:5, step:0.1,
        size:'xl',
        showCaption: false,
        showClear: false,
        animate: false,
    });

    $rateVisit.on('rating:change', (e, val) => {
        $('#rate-visit-container').data('rating-value', val);
    });

    $('#rate-visit-button').click((e) => {
        const $container = $('#rate-visit-container');
        const id = $container.data('visit-id');
        const rating = Math.round($container.data('rating-value') * 10.0);
        console.log(rating);

        const options = {
            data: {'updates': {'Rating': rating}},
            method: 'POST',
            complete: confirmRating
        };

        // TODO: use own ajax library instead
        $.ajax('/api/visit/'+ id, options);
    });



});

function confirmRating(jqXHR, status){
    console.log('It worked!');
}