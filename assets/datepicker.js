
const datepickerSettings = {
    showOtherMonths: true,
    selectOtherMonths: true,
    dateFormat: 'yy-mm-dd',
    showWeek: true,
    firstDay: 1,
    weekHeader: 'V',
    dayNamesMin: ['Sö', 'Må', 'Ti', 'On', 'To', 'Fr', 'Lö'],
    monthNames: ["Januari", "Februari", "Mars", "April", "Maj", "Juni", "Juli", "Augusti", "September", "Oktober", "November", "December"],
    onSelect: function (dateText, i){
        this.value = dateText;
        if(dateText !== i.lastVal){
            const event = new Event('change');
            this.dispatchEvent(event);
        }

    }
};

export default datepickerSettings;