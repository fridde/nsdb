'use strict';

import $ from 'jquery';

class Response {

    static insertApiKey = (data, jqXHR, textStatus) => {
        $('#api-key-field').val(data['key']);
    }

    static showNewStaffRow = (data, jqXHR, textStatus) => {
        if(data['success'] !== true){
            throw 'The request did not succeed.';
        }
        const row = $('#staff-table tbody tr').filter('[data-id="'+ data['temp_id'] + '"]');
        const newId = data['user_id'];
        row.data('id', newId).attr('data-id', newId);
        row.removeClass('d-none').addClass('bg-success');
    }

    static showUpdateToast = (data, jqXHR, textStatus) => {

    }

    static darkenRemovedUserRow = (data, jqXHR, textStatus) => {
        if(data['success']){
            const userId = data['user_id'];
            $(`tr[data-id="${userId}"]`).addClass('text-decoration-line-through text-black-50');
        } else {
            // show data['error']
        }
        // TODO: implement this method
    }

    static updateNoteId = (data, jqXHR, textStatus) => {
        if(data['success']){
            let noteId = data['note_id'];
            $('#note-for-visit-textarea').data('note-id', noteId).attr('data-note-id', noteId);
        }
    }

}

export default Response;