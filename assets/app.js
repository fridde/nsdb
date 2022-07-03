'use strict';

import $ from 'jquery';
import 'jqueryui';

import {Modal} from 'bootstrap';

import './styles/app.css';

import Edit from './Edit';
import Update from "./Update";

$(document).ready(() => {

    $('#add-staff-member-btn').click(Update.showAddStaffModal)

    $('.edit-staff-row-btn').click(Update.showEditStaffModal);

    $('#edit-user-modal input[name="Mail"]').change(Edit.checkMailField);

    $('#save-user-data').click(Update.saveUserData);

    $('#delete-user').click(Update.deleteUser);

    const approveUserModalDiv = $('#approve-user-modal');
    if(approveUserModalDiv.length){
        const approveUserModal = Modal.getOrCreateInstance('#approve-user-modal');
        approveUserModal.show();
        $('#approve-user-submit').click(Update.approveUsers)
    }

    $('div#register-user').find('button').click(Update.registerAsUser);

    $('.editable').change(Edit.change);

    $('input.form-range').each((i, el) => {
        $(el).change(Edit.change)
        $(el).on('input', Edit.updateRangeLabel);
    });
});


