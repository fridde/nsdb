"use strict";

import $ from "jquery";
import { Toast } from "bootstrap";

class Response {
  static insertApiKey = (data, jqXHR, textStatus) => {
    $("#api-key-field").val(data["key"]);
  };

  static showNewStaffRow = (data, jqXHR, textStatus) => {
    const row = $("#staff-table tbody tr").filter('[data-id="' + data["temp_id"] + '"]');
    const newId = data["user_id"];
    row.data("id", newId).attr("data-id", newId);
    row.removeClass("d-none").addClass("bg-success");

    let fullName = row.find('td[data-field="FirstName"]').text();
    fullName += " " + row.find('td[data-field="LastName"]').text();
    this.addToUserSelector(newId, fullName);
  };

  static addToUserSelector = (userId, fullName) => {
    let option = document.createElement("option");
    option.text = fullName;
    option.setAttribute("value", userId);
    $(".user-selector").append(option);
  };

  static showSuccessfulUpdateToast = (data, jqXHR, textStatus) => {
    this.showToast("Uppgifterna uppdaterades", true);
  };

  static showToast = (text, success) => {
    const toastSelector = "#update-toast-" + (success ? "success" : "error");
    const $toast = $(toastSelector);
    const toastInstance = Toast.getOrCreateInstance(toastSelector);

    const $toastHeader = $(toastSelector + " .toast-header");
    const $toastBody = $(toastSelector + " .toast-body");

    $toastBody.html(text);
    // const delay = (success ? 5000 : 999999);
    // $toast.data('bs-delay', delay).attr('data-bs-delay', delay); // weird hack because autohide doesn't overrule bs-delay
    // $toast.data('bs-autohide', success).attr('data-bs-autohide', success);

    $toast.toggleClass("bg-success", success).toggleClass("bg-danger", !success);
    toastInstance.show();
  };

  static darkenRemovedUserRow = (data, jqXHR, textStatus) => {
    const userId = data["user_id"];
    $(`tr[data-id="${userId}"]`).addClass("text-decoration-line-through text-black-50");
  };

  static confirmSavedPlannedVisits = (data, jqXHR, textStatus) => {
    this.showToast("Besöken sparades!", true);
    console.log(textStatus);
  };

  static updateNoteId = (data, jqXHR, textStatus) => {
    if (data["success"]) {
      let noteId = data["note_id"];
      $("#note-for-visit-textarea").data("note-id", noteId).attr("data-note-id", noteId);
    }
  };

  static redirectToConfirmationPage = (data, jqXHR, textStatus) => {
    if (data["success"]) {
      window.location.href = $("#registration-confirmation-url").data("url");
    }
  };

  static updateUnknownProfiles = (data, jqXHR, textStatus) => {
    if (data["success"]) {
      $("#unknown-profiles").val(data["updated_profiles"]);
      //toast good update
    } else {
        // toast bad update
    }
  };

  static reloadPage = () => {
    window.location.reload();
  };

  static showError = (data, jqXHR, textStatus) => {
    console.log(data.responseJSON.message);
    let message = "<div>" + data.responseJSON.message + "</div>";

    this.showToast(message, false);
  };
}

export default Response;
