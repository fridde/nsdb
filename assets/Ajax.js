'use strict';

import $ from 'jquery';


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

    responseHandler = this.genericResponse

    setResponseHandler = (handler) => {
        //this.settings.complete = handler;
        this.responseHandler = handler;
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
        return $.ajax(this.settings).then(this.responseHandler);
    }

    static createNew = () => {
        return new this();
    }

}

export default Ajax;