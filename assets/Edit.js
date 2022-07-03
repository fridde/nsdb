'use strict';

import $ from 'jquery';

import Update from './Update';
import {Toast} from 'bootstrap';


class Edit {

    static DIRECTION_MODAL_TO_ROW = 'mtr';
    static DIRECTION_ROW_TO_MODAL = 'rtm';

    static change = (event) => {
        const $this = $(event.target);
        const changeType = $this.closest('[data-edit-type]').data('edit-type');

        switch (changeType) {
            case "group-attribute":
                const attribute = $this.data('attribute');
                const value = $this.attr('value') ?? $this.val();
                const groupId = $this.closest('.group-container').data('group-id');
                Update.saveGroupData(groupId, {[attribute]: value});
                break;
        }
    }


    static ajaxComplete = (jqXHR, status) => {
        const data = jqXHR['responseJSON'];
        console.log(data);
    }


    static showUpdateStatus = (_, status) => {
        // const data = jqXHR.responseJSON;
        const toastSelector = '#update-toast';
        const $toast = $(toastSelector);
        const $toastBody = $('#toast-body');
        const toastInstance = Toast.getOrCreateInstance(toastSelector);

        const success = status === 'success';
        const statusKey = success ? 'success' : 'failure';
        const values = {
            "success" : {
                "text": 'Uppgifterna uppdaterades',
                "delay": 5000
            },
            "failure": {
                "text": 'Fel vid uppdatering. Försök igen eller kontakta admin!',
                "delay": 10000
            }
        };

        $toastBody.text(values[statusKey]['text']);
        $toast.data('bs-delay', values[statusKey]['delay']);
        $toast.toggleClass('bg-success', success).toggleClass('bg-danger',!success);

        toastInstance.show();
    }

    static updateRangeLabel = (event) => {
        const target = $('#' + $(event.target).data('indicator'));
        const val = $(event.target).val();
        $(event.target).attr('value', val);
        target.text(val);
    }

    static transferBetweenModalAndRow = (direction, row = null) => {
        if (direction === this.DIRECTION_MODAL_TO_ROW) {
            const data = this.getDataFromModal();
            this.setDataToRow(data);
        } else if (direction === this.DIRECTION_ROW_TO_MODAL) {
            const data = this.getDataFromRow(row);
            this.setDataToModal(data);
        } else {
            throw 'direction ' + direction + ' is not defined';
        }
    }



    static getDataFromModal = () => {
        const data = {};
        $('#edit-user-modal input').each((i, el) => {
            if (!el.hasAttribute('name')) {
                return;
            }
            data[$(el).attr('name')] = $(el).val();
        });

        return data;
    }


    static setDataToModal = (data) => {
        $('#edit-user-modal input').each((i, el) => {
            if (!el.hasAttribute('name')) {
                return;
            }
            $(el).val(data[$(el).attr('name')]);
        });
    }


    static getDataFromRow = (row) => {
        const data = {};
        data['id'] = row.data('id');

        row.find('td').each((i, el) => {
            if (!el.hasAttribute('data-field')) {
                return;
            }
            data[$(el).data('field')] = $(el).html();
        });
        return data;
    }


    static setDataToRow = (data) => {
        const row = $('#staff-table tbody tr').filter('[data-id="' + data['id'] + '"]');
        row.find('td').each((i, el) => {
            if (!el.hasAttribute('data-field')) {
                return;
            }
            $(el).html(data[$(el).data('field')]);
        });
    }

    static checkMailField = (e) => {
        const mailRegEx = /.+@.+/;
        const mail = String($(e.target).val());
        const isValid = mailRegEx.test(mail);
        $('#save-user-data').prop('disabled', !isValid);
        if(mail !== ""){
            $(e.target).toggleClass('is-invalid', !isValid).toggleClass('is-valid', isValid);
        }



        //
        // if(mail.includes('@')){
        //     btn.removeAttribute('disabled');
        // } else {
        //     btn.setAttribute('disabled');
        // }
    }

    static setCookie = (name, value, expirationInDays) => {
        const d = new Date();
        d.setTime(d.getTime() + (expirationInDays * 24 * 60 * 60 * 1000));
        const expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/;Secure";
    }

}

export default Edit;