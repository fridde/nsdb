'use strict';

import $ from 'jquery';

import Ajax from "./Ajax";
import Response from "./Response";

class Planyear {

    constructor() {
        this.current = $('#start-cell');
        this.new = null;
        this.col = 0;
        this.topicData = $('#valid-keys').data('valid-keys');
        this.validKeys = Object.keys(this.topicData);

        this.current.addClass("plan-year-active");
    }

    reactToKey = (key) => {
        const code = key.code.toLowerCase().replace('key', '');
        if (this.hasOwnProperty(code)) {
            this[code]();
            if (this.new.length === 0 || this.new.hasClass('not-choosable')) {
                return;
            }
            this.changeCell();
            return;
        }

        if (this.letterIsValid(code)) {
            this.writeLetter(code);
            return;
        }
        if (this.isDeleteKey(code)) {
            this.clearCell();
            return;
        }
        if (this.isUncounterKey(code)) {
            this.toggleCountability();
        }
    }

    upOrDown = (direction) => {
        const col = this.current.index();
        const parent = this.current.parent();
        const other = direction === 'up' ? parent.prev() : parent.next();
        this.new = other.children().eq(col);
    }

    arrowup = () => {
        this.upOrDown('up');
    }

    arrowdown = () => {
        this.upOrDown('down');
    }

    arrowright = () => {
        this.new = this.current.next();
    }

    arrowleft = () => {
        this.new = this.current.prev();
    }

    letterIsValid = (code) => {
        return this.validKeys.includes(code);
    }

    writeLetter = (code) => {
        this.current.text(code.toUpperCase());
        this.current.data('letter', code).attr('data-letter', code);

        this.changeCounter(code, 'up');
        this.current.addClass('segment-' + this.getSegment(code)).addClass('chosen');
    }

    getSegment = (code) => {
        code = this.standardizeCode(code);
        return this.topicData[code]['segment'];
    }

    standardizeCode = (code) => {
        return code.replace(/\W/g, "").toLowerCase(); // to remove brackets etc
    }

    isDeleteKey = (code) => {
        return code === 'delete';
    }

    isUncounterKey = (code) => {
        return ['shiftleft', 'slash'].includes(code);
    }

    clearCell = () => {
        const code = this.current.text();
        this.current.text('');
        this.current.data('letter', '').attr('data-letter', '');
        this.current.removeClass('segment-' + this.getSegment(code)).removeClass('chosen');

        this.changeCounter(code, 'down');
    }

    changeCounter = (code, direction) => {
        code = this.standardizeCode(code)
        let diff = 1.0 / this.topicData[code]['cpg'];
        diff = direction === 'up' ? diff : diff * (-1.0);
        const counter = $('#topic-counter-' + code);
        const currentVal = parseFloat(counter.data('value'));
        const newVal = diff + currentVal;
        counter.text(newVal.toFixed(1)).data('value', newVal).attr('data-value', newVal);
    }

    toggleCountability = () => {
        let currentText = this.current.text();
        if (currentText.trim() === '') {
            return;
        }
        let code = currentText;
        let direction = 'down';
        let bystander = 1;
        if (code.startsWith('(')) {
            code = code.replace(/\W/g, "");
            currentText = code;
            direction = 'up';
            bystander = 0
        } else {
            currentText = '(' + currentText + ')';
        }
        this.changeData(this.current, 'bystander', bystander);
        this.current.text(currentText);
        this.changeCounter(code, direction);
    }

    changeCell = () => {
        this.current.toggleClass("plan-year-active")
        this.new.toggleClass("plan-year-active");
        this.current = this.new;
    }

    savePlannedVisits = () => {
        const visits = [];

        $('.plan-year-choices').each((i, el) => {
            const letter = $(el).data('letter');
            if (letter !== "") {
                visits.push({
                    'letter': letter,
                    date:  $(el).data('date'),
                    colleague: $(el).data('colleague'),
                    bystander: $(el).data('bystander')
                });
            }
        });

        Ajax.createNew()
            .setUrl('/api/save-planned-visits')
            .addToData('visits', visits)
            .setSuccessHandler(Response.confirmSavedPlannedVisits)
            .send();
    }

    changeData = (jqElement, dataKey, value) => {
        jqElement.data(dataKey, value).attr('data-' + dataKey, value);
    }
}


$(document).ready(() => {

    const planYear = new Planyear();

    $(document).keydown(planYear.reactToKey);

    $("#save-planned-year").click(planYear.savePlannedVisits);
});