'use strict';

import $ from 'jquery';
import 'jquery-ui';
import 'jquery-ui/ui/widgets/datepicker';
import 'jquery-ui/ui/widgets/sortable';
import 'jquery-ui/ui/widgets/draggable';
import 'jquery-ui/ui/disable-selection';

import Update from "./Update";
import datepickerSettings from "./datepicker";
import DistributeVisits from "./DistributeVisits";
import BatchEdit from "./BatchEdit";

import './styles/admin.css';
import Edit from "./Edit";

$(document).ready(() => {

    $('.nsdb-datepicker').datepicker(datepickerSettings);

    $('#schedule-colleagues td.toggle-label').on('click', Update.changeColleagueSchedule);

    $('#school-visit-order').sortable({stop: Update.changeSchoolVisitOrder});

    $('#distribute-visits-dates span').draggable(DistributeVisits.draggableSettings).disableSelection();
    $('.fixed-visits').disableSelection();

    $('#send-distribute-visits').on('click', DistributeVisits.sendVisitsWithGroups);

    $('#bus-settings td.toggle-label').on('click', Update.changeBusSetting);

    $('#save-unknown-locations').on('click', Update.saveUnknownLocations);

    $('#confirm-bus-orders td.toggle-label').on('click', Update.confirmBusOrder);

    $('#create-api-key').on('click', Update.getApiKey);

    $('#note-for-visit-textarea').on('change', Update.changeNoteForVisit)

    $('#copy-food-order').on('click', (e) => {
        let text = $('#order-food-textarea').val();
        navigator.clipboard.writeText(text);
    });

    $('.add-groups-button').on('click', Update.addMultipleGroups);

    $('#next_school_admin_mail').on('change', Update.changeNextSchoolAdminMailDate);

    $('#multi-access-user-list select.school-selector')
        .on('change', Update.saveMultiAccessUserSchools);

    $('#add-multi-access-user-button').on('click', Edit.addMultiAccessUser);

    $('#lookup-profiles').on('click', Update.lookupProfiles);



    if($('#entity-table').length){  // == exists
        const BE = new BatchEdit();

        $('input[name=segment-selector]').on('change', BE.filterForSegment);
        $('input[name=start-year-selector]').on('change', BE.filterforStartYear);

        $('#reset-action').on('click', BE.reset);
        $('#choose-all-action').on('click', BE.chooseAll);
        $('#choose-none-action').on('click', BE.chooseNone);
        $('#keep-selected-action').on('click', BE.keepSelected);
        $('#original-name-action').on('click', BE.originalName);
        $('#increase-segment-action').on('click', BE.increaseSegment);
        $('#rename-to-parts-action').on('click', BE.renameToParts);
        $('#save-visible-action').on('click', BE.openSaveModal);
    }

});



