'use strict';

import $ from "jquery";
import Ajax from "./Ajax";

class DistributeVisits {

    coordinates = [];

    constructor() {
        this.setCoordinates();
    }


    static draggableSettings = {
        snap: '.date-target',
        containment: '.distribute-visits-container'
    }

    static sendVisitsWithGroups = (e) => {
        const $self = new DistributeVisits();
        const visits = $('.choosable-date').map(function (i, e) {
            return {
                visit: $(e).data('visit-id'),
                group: $self.identifyGroup(e)
            };
        }).get().filter(obj => false !== obj['group']);

        Ajax.createNew()
            .setUrl('/api/distribute-visits')
            .addToData('visits', visits)
            .send()
    }

    identifyGroup = (dateSpanElement) => {
        const $e = $(dateSpanElement);
        const x = $e.offset().left + (0.5 * $e.outerWidth());
        const y = $e.offset().top + (0.5 * $e.outerHeight());

        for (const v of this.coordinates) {
            const requirements = [
                x > v.x,
                x < (v.x + v.dx),
                y > v.y,
                y < (v.y + v.dy)
            ];

            if (requirements.every(Boolean)) { // means that all are true
                return v.groupId;
            }
        }
        return false;
    }

    setCoordinates = () => {
        this.coordinates = $(".date-target").map(function (i, e) {
            const $e = $(e);
            return {
                groupId: $e.data('group-id'),
                x: $e.offset().left,
                dx: $e.outerWidth(),
                y: $e.offset().top,
                dy: $e.outerHeight()
            }
        }).get();
    }
}

export default DistributeVisits;