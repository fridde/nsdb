import $ from "jquery";
import Ajax from "./Ajax";
import Edit from "./Edit";
import Response from "./Response";
import { Modal } from "bootstrap";

class Update {
    static changeColleagueSchedule = e => {
        const $td = $(e.target);
        const colleagueId = $td.data("colleague-id");

        Ajax.createNew()
            .addToData("visit_id", $td.closest(".visit-row").data("id"))
            .addToData("direction", $td.hasClass("off-duty") ? "add" : "remove")
            .setUrl("/api/schedule-colleague/" + colleagueId)
            .send();

        $td.toggleClass("off-duty");
    };

    static changeSchoolVisitOrder = e => {
        const schoolIds = $(e.target).closest(".sortable").sortable("toArray", {
            attribute: "data-id",
        });
        Ajax.createNew().setUrl("/api/school-visit-order").addToData("school-order", schoolIds).send();
    };

    static changeBusSetting = e => {
        const $td = $(e.delegateTarget);
        const locationId = $td.data("location-id");
        const schoolId = $td.closest(".bus-school-row").data("school-id");
        const direction = $td.hasClass("active") ? "remove" : "add";

        Ajax.createNew()
            .setUrl("/api/set-bus-setting/" + schoolId + "/" + locationId)
            .addToData("direction", direction)
            .send();

        $td.toggleClass("active");
        $td.find(".fas").toggleClass("fa-minus").toggleClass("fa-bus");
    };

    static confirmBusOrder = e => {
        const $td = $(e.delegateTarget);
        const visitId = $td.data("visit-id");
        const direction = $td.hasClass("active") ? "unbook" : "confirm";

        Ajax.createNew()
            .setUrl("/api/confirm-bus-order/" + visitId)
            .addToData("direction", direction)
            .send();

        $td.toggleClass("active");
        $td.find(".fas").toggleClass("fa-question").toggleClass("fa-check");
    };

    static saveUnknownLocations = e => {
        let locations = {};
        $(".location-translation").each((i, el) => {
            locations[$(el).data("lookup-name")] = $(el).val();
        });
        Ajax.createNew()
            .setUrl("/api/save-bus-data")
            .addToData("locations", locations)
            .setSuccessHandler(Response.reloadPage)
            .send();
    };

    static showEditStaffModal = e => {
        const row = $(e.target).closest("tr");
        Edit.transferBetweenModalAndRow(Edit.DIRECTION_ROW_TO_MODAL, row);

        const modal = Modal.getOrCreateInstance("#edit-user-modal");
        $("#edit-user-modal .modal-title").text("Ändra uppgifter");
        $('#edit-user-modal input[name="Mail"]').trigger("change");
        $("#delete-user").not(".dont-show-to-users").removeClass("d-none");

        modal.show();
    };

    static showAddStaffModal = e => {
        const row = $("#staff-table tbody tr").filter('[data-id="new"]');
        const newerRow = row.clone();
        row.after(newerRow);
        const newId = "new_" + this.createRandomString();
        row.data("id", newId).attr("data-id", newId);

        Edit.transferBetweenModalAndRow(Edit.DIRECTION_ROW_TO_MODAL, row);
        const modal = Modal.getOrCreateInstance("#edit-user-modal");
        $("#edit-user-modal .modal-title").text("Lägg till pedagog");
        $("#delete-user").addClass("d-none");
        modal.show();
    };

    static saveUserData = e => {
        const modal = Modal.getOrCreateInstance("#edit-user-modal");
        modal.hide();

        Edit.transferBetweenModalAndRow(Edit.DIRECTION_MODAL_TO_ROW);
        const data = Edit.getDataFromModal();

        if (data["id"].startsWith("new")) {
            data["School"] = $("#requested-school").data("school");
            this.saveNewUser(data);
        } else {
            this.updateExistingUser(data);
        }
    };

    static saveNewUser = data => {
        Ajax.createNew()
            .setUrl("/api/create/user")
            .addToData("user", data)
            .setSuccessHandler(Response.showNewStaffRow)
            .send();
    };

    static updateExistingUser = data => {
        Ajax.createNew()
            .setUrl("/api/user/" + data["id"])
            .addToData("updates", data)
            .send();
    };

    static deleteUser = e => {
        const modal = Modal.getOrCreateInstance("#edit-user-modal");
        modal.hide();

        const id = $("#modal-user-id-field").val();

        Ajax.createNew()
            .setUrl("/api/delete/user/" + id)
            .setSuccessHandler(Response.darkenRemovedUserRow)
            .send();
    };

    static approveUsers = e => {
        const approveUserModalDiv = $("#approve-user-modal");
        const approveUserModal = Modal.getOrCreateInstance("#approve-user-modal");
        approveUserModal.hide();
        const data = { yes: [], no: [], unsure: approveUserModalDiv.data("ignore-approval") };
        let userId, unsureRows;

        approveUserModalDiv.find("input:checked").each((i, el) => {
            userId = $(el).data("pending-user-id");
            data[$(el).val()].push(userId);
        });
        const staffTableRows = $("#staff-table tr");
        data["no"].forEach(userId => staffTableRows.filter('[data-id="' + userId + '"]').remove());
        data["unsure"].forEach(userId => {
            unsureRows = staffTableRows.filter('[data-id="' + userId + '"]');
            unsureRows.addClass("uneditable");
            unsureRows.find("button").hide();
        });

        Edit.setCookie("ignore_approval", JSON.stringify(data["unsure"]), 90);

        Ajax.createNew().setUrl("/api/approve").addToData("approvals", data).send();

        // approveUserModal.hide();
    };

    static registerAsUser = e => {
        const userData = $("#register-user").data("user");
        userData["school"] = $("#register-user select").val();

        Ajax.createNew()
            .setUrl("/api/register")
            .addToData("user", userData)
            .setSuccessHandler(Response.redirectToConfirmationPage)
            .send();
    };

    static getApiKey = e => {
        Ajax.createNew().setUrl("/api/get-valid-cron-key").setSuccessHandler(Response.insertApiKey).send();
    };

    static saveGroupData = (groupId, data) => {
        Ajax.createNew()
            .setUrl("/api/group/" + groupId)
            .addToData("updates", data)
            .setSuccessHandler(Response.showSuccessfulUpdateToast)
            .send();
    };

    static changeNoteForVisit = e => {
        let text = $(e.target).val();
        let note = $(e.target).data("note-id");
        let ajax = Ajax.createNew()
            .setUrl("/api/note/" + note)
            .addToData("text", text)
            .setSuccessHandler(Response.updateNoteId);

        if (note === "new") {
            ajax.addToData("visit", $(e.target).data("visit-id"));
        }
        ajax.send();
    };

    static addMultipleGroups = e => {
        const container = $(e.target).closest("[data-segment]");
        const data = {
            groupNumbers: {},
            segment: container.data("segment"),
            startYear: container.find("input[name=start-year]").val(),
        };

        container.find(".group-number").each((i, el) => {
            data.groupNumbers[$(el).data("school")] = $(el).val();
        });

        const ajax = Ajax.createNew()
            .setUrl("/api/add-groups")
            .addToData("data", data)
            .setSuccessHandler(Response.showUpdateToast);

        ajax.send();
    };

    static saveMultipleGroupNames = data => {
        const ajax = Ajax.createNew()
            .setUrl("/api/batch-rename-groups")
            .addToData("data", data)
            .setSuccessHandler(Response.showUpdateToast);

        ajax.send();
    };

    static saveMultiAccessUserSchools = e => {
        const userId = $(e.target).data("user-id");
        const schools = $(e.target)
            .find("option:selected")
            .map((i, el) => $(el).val())
            .get();
        const ajax = Ajax.createNew()
            .setUrl("/api/save-multi-access-user/" + userId)
            .addToData("schools", schools.join())
            .setSuccessHandler(Response.showUpdateToast);

        this.cancelAllTimeOuts();
        this.addTimeOutId(setTimeout(ajax.send, 3000));
    };

    static changeNextSchoolAdminMailDate = e => {
        const ajax = Ajax.createNew()
            .setUrl("/api/change-editable-setting")
            .addToData("setting", "next_school_admin_mail")
            .addToData("value", $(e.target).val())
            .setSuccessHandler(Response.showUpdateToast);

        ajax.send();
    };

    static lookupProfiles = e => {
        const ajax = Ajax.createNew()
            .setUrl("/api/lookup-profiles")
            .addToData("values", $("#unknown-profiles").val())
            .addToData('data-type', $('#given-profile-type-selector').val())
            .setSuccessHandler(Response.updateUnknownProfiles);

        ajax.send();
    };

    static createRandomString = () => {
        return Math.random().toString(36).substring(2, 7);
    };

    static addTimeOutId = timeOutId => {
        if (!window.hasOwnProperty("timeOutIds")) {
            window.timeOutIds = [];
        }
        window.timeOutIds.push(timeOutId);
    };

    static cancelAllTimeOuts = () => {
        if (!window.hasOwnProperty("timeOutIds")) {
            return;
        }
        window.timeOutIds.forEach(clearTimeout);
    };
}

export default Update;
