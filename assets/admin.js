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

    $('#schedule-colleagues td.toggle-label').click(Update.changeColleagueSchedule);

    $('#school-visit-order').sortable({stop: Update.changeSchoolVisitOrder});

    $('#distribute-visits-dates span').draggable(DistributeVisits.draggableSettings).disableSelection();
    $('.fixed-visits').disableSelection();

    $('#send-distribute-visits').click(DistributeVisits.sendVisitsWithGroups);

    $('#bus-settings td.toggle-label').click(Update.changeBusSetting);

    $('#save-unknown-locations').click(Update.saveUnknownLocations);

    $('#confirm-bus-orders td.toggle-label').click(Update.confirmBusOrder);

    $('#create-api-key').click(Update.getApiKey);

    $('#note-for-visit-textarea').change(Update.changeNoteForVisit)

    $('#copy-food-order').click((e) => {
        let text = $('#order-food-textarea').val();
        navigator.clipboard.writeText(text);
    });

    $('.add-groups-button').click(Update.addMultipleGroups);

    $('#next_school_admin_mail').change(Update.changeNextSchoolAdminMailDate);

    $('#multi-access-user-list select.school-selector')
        .change(Update.saveMultiAccessUserSchools);

    $('#add-multi-access-user-button').click(Edit.addMultiAccessUser);



    if($('#entity-table').length){  // == exists
        const BE = new BatchEdit();

        $('input[name=segment-selector]').change(BE.filterForSegment);
        $('input[name=start-year-selector]').change(BE.filterforStartYear);

        $('#reset-action').click(BE.reset);
        $('#choose-all-action').click(BE.chooseAll);
        $('#choose-none-action').click(BE.chooseNone);
        $('#keep-selected-action').click(BE.keepSelected);
        $('#original-name-action').click(BE.originalName);
        $('#increase-segment-action').click(BE.increaseSegment);
        $('#rename-to-parts-action').click(BE.renameToParts);
        $('#save-visible-action').click(BE.openSaveModal);
    }

});



