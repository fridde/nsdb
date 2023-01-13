'use strict';

import $ from 'jquery';
import Response from "./Response";

class Ajax {

    constructor() {
    }

    genericResponse = (data, jqHXR, textStatus) => {
        console.log("The generic handler was called. The request was a " + textStatus);
    }



    settings = {
        url: '/',
        method: 'POST',
        dataType: 'json',
        data: {},
        beforeSend: function (jqXHR, settings) {
            const $apiKey = $('#api-key');
            if($apiKey.length){
                settings.data += '&key=' + $apiKey.data('api-key');
            }
        }
    }

    successHandler = this.genericResponse

    failureHandler = Response.showError

    setSuccessHandler = (handler) => {
        this.successHandler = handler;
        return this;
    }

    setFailureHandler = (handler) => {
        this.failureHandler = handler;
        return this;
    }

    addToData = (key, value) => {
        this.settings.data[key] = value;
        return this;
    }

    setUrl = (url) => {
        this.settings.url = url;
        return this;
    }

    send = () => {
        return $.ajax(this.settings)
            // .fail(() => console.log('FAIL!!!'));
            .fail(this.failureHandler)
            .done(this.successHandler);
    }

    static createNew = () => {
        return new this();
    }

}

export default Ajax;