import $ from "jquery";
import Ajax from "./Ajax";
import Edit from "./Edit";
import Response from "./Response";
import {Modal} from 'bootstrap';

class Update {

    static changeColleagueSchedule = (e) => {
        const $td = $(e.target);
        const colleagueId = $td.data('colleague-id');

        Ajax.createNew()
            .addToData('visit_id', $td.closest('.visit-row').data('id'))
            .addToData('direction', ($td.hasClass('off-duty') ? 'add' : 'remove'))
            .setUrl('/api/schedule-colleague/' + colleagueId)
            .send();

        $td.toggleClass('off-duty');
    }

    static changeSchoolVisitOrder = (e) => {
        const schoolIds = $(e.target).closest('.sortable').sortable('toArray', {
            attribute: 'data-id'
        });
        Ajax.createNew()
            .setUrl('/api/school-visit-order')
            .addToData('school-order', schoolIds)
            .send()
    }

    static changeBusSetting = (e) => {
        const $td = $(e.delegateTarget);
        const locationId = $td.data('location-id');
        const schoolId = $td.closest('.bus-school-row').data('school-id');
        const direction = $td.hasClass('active') ? 'remove' : 'add';

        Ajax.createNew()
            .setUrl('/api/set-bus-setting/' + schoolId + '/' + locationId)
            .addToData('direction', direction)
            .send();

        $td.toggleClass('active');
        $td.find('.fas').toggleClass('fa-minus').toggleClass('fa-bus');
    }

    static confirmBusOrder = (e) => {
        const $td = $(e.delegateTarget);
        const visitId = $td.data('visit-id');
        const direction = $td.hasClass('active') ? 'unbook' : 'confirm';

        Ajax.createNew()
            .setUrl('/api/confirm-bus-order/' + visitId)
            .addToData('direction', direction)
            .send();

        $td.toggleClass('active');
        $td.find('.fas').toggleClass('fa-question').toggleClass('fa-check');
    }

    static showEditStaffModal = (e) => {
        const row = $(e.target).closest('tr');
        Edit.transferBetweenModalAndRow(Edit.DIRECTION_ROW_TO_MODAL, row);

        const modal = Modal.getOrCreateInstance('#edit-user-modal');
        $('#edit-user-modal .modal-title').text('??ndra uppgifter');
        $('#edit-user-modal input[name="Mail"]').trigger('change');
        $('#delete-user').not('.dont-show-to-users').removeClass('d-none');

        modal.show();
    }

    static showAddStaffModal = (e) => {
        const row = $('#staff-table tbody tr').filter('[data-id="new"]');
        const newerRow = row.clone();
        row.after(newerRow);
        const newId = "new_" + this.createRandomString();
        row.data('id', newId).attr('data-id', newId);

        Edit.transferBetweenModalAndRow(Edit.DIRECTION_ROW_TO_MODAL, row);
        const modal = Modal.getOrCreateInstance('#edit-user-modal');
        $('#edit-user-modal .modal-title').text('L??gg till pedagog');
        $('#delete-user').addClass('d-none');
        modal.show();
    }

    static saveUserData = (e) => {
        const modal = Modal.getOrCreateInstance('#edit-user-modal');
        modal.hide();

        Edit.transferBetweenModalAndRow(Edit.DIRECTION_MODAL_TO_ROW);
        const data = Edit.getDataFromModal();

        if (data['id'].startsWith('new')) {
            data['School'] = $('#requested-school').data('school');
            this.saveNewUser(data);
        } else {
            this.updateExistingUser(data);
        }
    }

    static saveNewUser = (data) => {
        Ajax.createNew()
            .setUrl('/api/create/user')
            .addToData('user', data)
            .setResponseHandler(Response.showNewStaffRow)
            .send();
    }

    static updateExistingUser = (data) => {
        Ajax.createNew()
            .setUrl('/api/user/' + data['id'])
            .addToData('updates', data)
            .send();
    }

    static deleteUser = (e) => {
        const modal = Modal.getOrCreateInstance('#edit-user-modal');
        modal.hide();

        const id = $('#modal-user-id-field').val();

        Ajax.createNew()
            .setUrl('/api/delete/user/' + id)
            .setResponseHandler(Response.darkenRemovedUserRow)
            .send();
    }

    static approveUsers = (e) => {
        const approveUserModalDiv = $('#approve-user-modal');
        const approveUserModal = new Modal('#approve-user-modal');
        const data = {'yes': [], 'no': [], 'unsure': approveUserModalDiv.data('ignore-approval')};
        let userId, unsureRows;
        approveUserModal.hide();
        approveUserModalDiv.find('input:checked').each((i, el) => {
                userId = $(el).data('pending-user-id');
                data[$(el).val()].push(userId);
            }
        );
        const staffTableRows = $('#staff-table tr');
        data['no'].forEach((userId) => staffTableRows.filter('[data-id="' + userId + '"]').remove());
        data['unsure'].forEach(function (userId) {
            unsureRows = staffTableRows.filter('[data-id="' + userId + '"]');
            unsureRows.addClass('.uneditable');
            unsureRows.find('button').hide();
        });

        Edit.setCookie('ignore_approval', JSON.stringify(data['unsure']), 90);

        Ajax.createNew()
            .setUrl('/api/approve')
            .addToData('approvals', data)
            .send();

        approveUserModal.hide();
    }

    static registerAsUser = (e) => {
        const userData = registerUserDiv.data('user');
        userData['school'] = $('#register-user select').val();

        Ajax.createNew()
            .setUrl('/api/register')
            .addToData('user', userData)
            .send();
    }

    static getApiKey = (e) => {
        Ajax.createNew()
            .setUrl('/api/get-valid-cron-key')
            .setResponseHandler(Response.insertApiKey)
            .send();
    }

    static saveGroupData = (groupId, data) => {
        Ajax.createNew()
            .setUrl('/api/group/' + groupId)
            .addToData('updates', data)
            .setResponseHandler(Edit.showUpdateStatus)
            .send();
    }

    static changeNoteForVisit = (e) => {
        let text = $(e.target).val();
        let note = $(e.target).data('note-id');
        let ajax = Ajax.createNew()
            .setUrl('/api/note/' + note)
            .addToData('text', text)
            .setResponseHandler(Response.updateNoteId);

        if (note === "new") {
            ajax.addToData('visit', $(e.target).data('visit-id'));
        }
        ajax.send();
    }

    static createRandomString = () => {
        return Math.random().toString(36).substring(2, 7);
    }
}

export default Update;