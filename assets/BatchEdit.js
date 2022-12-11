import $ from "jquery";
import 'jquery-ui';
import 'jquery-ui/ui/widgets/dialog';
import Update from "./Update";

class BatchEdit {

    constructor() {
        this.allRows = $('#entity-table tr');
        this.reset();

    }

    openSaveModal = () => {
        $('#save-confirmation').dialog({
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
                'Spara': () => {
                    BatchEdit.saveVisible(this.selectedRows);
                    $('#save-confirmation').dialog("close");
                },
                'Avbryt': function () {
                    $(this).dialog("close")
                }
            }
        });
    }

    update = () => {
        this.selectedRows.show();
        $(this.allRows).not(this.selectedRows).hide();
    }

    reset = (e) => {
        this.selectedRows = this.allRows;
        this.criteria = {};
        this.update();
        $(':checked').prop('checked', false);
        this.originalName();
    }

    chooseAll = (e) => {
        this.selectedRows.find('.inclusion-checkbox').prop('checked', true);
    }

    chooseNone = (e) => {
        this.selectedRows.find('.inclusion-checkbox').prop('checked', false);
    }

    keepSelected = (e) => {
        this.selectedRows = this.selectedRows.filter((i, el) => $(el).find('.inclusion-checkbox').prop('checked'));
        this.update();
    }

    originalName = (e) => {
        this.selectedRows.find('.new-name')
            .each((i, el) => $(el).val($(el).attr('placeholder')));
    }

    increaseSegment = (e) => {
        this.selectedRows.find('.new-name')
            .each((i, el) => {
                let oldValue = $(el).val();
                let segment = parseInt(oldValue.match(/\d/));
                $(el).val(oldValue.replace(/\d/, segment + 1));
            });
        //$(el).val($(el).attr('placeholder')));
    }

    renameToParts = (e) => {
        this.selectedRows.find('.new-name')
            .each((i, el) => {
                let newValue = $(el).val().replace(/(\d)([A-Z])/, '$1:orna, del $2');  // 5A  => 5:orna, del A
                $(el).val(newValue);
            });
    }

    filterForSegment = (e) => {
        this.filterFor(e, 'segment');
    }

    filterforStartYear = (e) => {
        this.filterFor(e, 'start-year');
    }

    static saveVisible = (selectedRows) => {
        let data = [];
        selectedRows.each((i, el) => {
            data.push({
                'group': $(el).data('group'),
                'name': $(el).find('.new-name').val()
            });
        });

        Update.saveMultipleGroupNames(data);
    }


    filterForAllCriteria = () => {
        this.selectedRows = this.allRows;
        for (let [dataName, dataValue] of Object.entries(this.criteria)) {
            dataValue = String(dataValue);
            this.selectedRows = this.selectedRows.filter(
                (i, el) => String($(el).data(dataName)) === dataValue
            );
        }
    }

    filterFor = (e, dataName) => {
        this.criteria[dataName] = $(e.target).val();
        this.filterForAllCriteria();
        this.update();
    }


}

export default BatchEdit;