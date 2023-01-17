'use strict';

import $ from 'jquery';
import Cookies from 'js-cookie';

import Ajax from "./Ajax";
import Response from "./Response";

class Planyear {

    constructor() {
        this.current = $('#' + $('#first-cell-id').data('first-cell-id'));
        this.currentPlan = {};
        this.new = null;
        this.col = 0;
        this.topicData = $('#valid-keys').data('valid-keys');
        this.validKeys = Object.keys(this.topicData);

        this.current.addClass("plan-year-active");
        if (Cookies.get('planned_year')) {
            $('#load-from-cookie').removeClass('d-none');
        }
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

        if (this.isProtectedCell()) {
            return;
        }

        if (this.isValidLetter(code)) {
            if (this.current.hasClass('chosen')) {
                console.error('Cell is already occupied. Delete first!')
                return;  // can't write over something, that would cause too much headache
            }
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

    isValidLetter = (code) => {
        return this.validKeys.includes(code);
    }

    writeLetter = (code) => {
        this.current.text(code.toUpperCase());
        this.saveToPlan(this.current, code + '_1')

        this.changeCounter(code, 'up');
        this.current.addClass(['segment-' + this.getSegment(code), 'chosen']);
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

    isProtectedCell = () => {
        return this.current.hasClass('protected-cell');
    }

    saveToPlan = (cell, value) => {
        this.currentPlan[cell.attr('id')] = value;
    }

    clearCell = () => {
        const code = this.current.text();
        this.current.text('');
        this.current.data('letter', '').attr('data-letter', '');
        this.current.removeClass(['segment-' + this.getSegment(code), 'chosen']);
        delete this.currentPlan[this.current.attr('id')];

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
        let assigned = 0;
        if (code.startsWith('(')) {
            code = code.replace(/\W/g, "");
            currentText = code;
            direction = 'up';
            assigned = 1;
        } else {
            currentText = '(' + currentText + ')';
        }
        this.saveToPlan(this.current, code + '_' + assigned)
        this.current.text(currentText);
        this.changeCounter(code, direction);
    }

    changeCell = () => {
        this.current.toggleClass("plan-year-active")
        this.new.toggleClass("plan-year-active");
        this.current = this.new;
    }

    writeVisitsToTable = (visits, existing) => {
        for (const [key, value] of Object.entries(visits)) {
            let cell = $('#' + key);

            let [code, assigned] = value.split('_');
            let classes = [
                'chosen',
                'segment-' + this.getSegment(code),
            ];
            if (existing) {
                classes.push('protected-cell', 'text-muted', 'small')
            }

            cell.addClass(classes);

            let letter = code.toUpperCase();
            if (parseInt(assigned) === 0) {
                letter = `(${letter})`;
            }
            cell.text(letter);
        }
    }

    commitPlannedVisits = () => {
        Ajax.createNew()
            .setUrl('/api/save-planned-visits')
            .addToData('visits', this.currentPlan)
            .setSuccessHandler(Response.confirmSavedPlannedVisits)
            .send();
    }

    loadExistingVisits = () => {
        const existingVisits = $('#existing-visits').data('existing-visits');
        this.writeVisitsToTable(existingVisits, true);
    }


    savePlanToCookie = () => {
        return;
        // TODO: Implement this function
        let planString = JSON.stringify(this.currentPlan)
        Cookies.set('planned_year', planString, {expires: 365});
        $('#saved-plan-textarea').text(planString);
        $('#load-from-cookie').removeClass('d-none');
    }

    loadPlanfromCookie = () => {
        return;
        // TODO: Implement this function
        let savedVisits = JSON.parse(Cookies.get('planned-year'));
        this.writeVisitsToTable(savedVisits, false);
    }

    changeData = (jqElement, dataKey, value) => {
        jqElement.data(dataKey, value).attr('data-' + dataKey, value);
    }
}


$(document).ready(() => {

    const planYear = new Planyear();

    $(document).keydown(planYear.reactToKey);

    $("#commit-planned-visits").click(planYear.commitPlannedVisits);
    $("#save-to-cookie").click(planYear.savePlanToCookie);
    $("#load-from-cookie").click(planYear.loadPlanfromCookie);
    $("#load-existing-visits").click(planYear.loadExistingVisits);

});