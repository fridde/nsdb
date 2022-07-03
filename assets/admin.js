'use strict';

import $ from 'jquery';
import 'jqueryui';

import Update from "./Update";
import datepickerSettings from "./datepicker";
import DistributeVisits from "./DistributeVisits";

import './styles/admin.css';

$(document).ready(() => {

    $('.nsdb-datepicker').datepicker(datepickerSettings);

    $('#schedule-colleagues td.toggle-label').click(Update.changeColleagueSchedule);

    $('#school-visit-order').sortable({stop: Update.changeSchoolVisitOrder});

    $('#distribute-visits-dates span').draggable(DistributeVisits.draggableSettings).disableSelection();
    $('.fixed-visits').disableSelection();

    $('#send-distribute-visits').click(DistributeVisits.sendVisitsWithGroups);

    $('#bus-settings td.toggle-label').click(Update.changeBusSetting);

    $('#confirm-bus-orders td.toggle-label').click(Update.confirmBusOrder);

    $('#create-api-key').click(Update.getApiKey);
});



