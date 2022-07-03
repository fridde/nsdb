
const datepickerSettings = {
    showOtherMonths: true,
    selectOtherMonths: true,
    dateFormat: 'yy-mm-dd',
    showWeek: true,
    firstDay: 1,
    dayNamesMin: ['Sö', 'Må', 'Ti', 'On', 'To', 'Fr', 'Lö'],
    monthNames: ["Januari", "Februari", "Mars", "April", "Maj", "Juni", "Juli", "Augusti", "September", "Oktober", "November", "December"],
    onSelect: function (dateText){
        this.value = dateText;
    }
};

export default datepickerSettings;