export default function orderCancellationReason({ options = [], reason = '', choice = '' } = {}) {
    const previousReason = typeof reason === 'string' ? reason : '';
    const previousChoice = choice === 'other' || options.includes(choice)
        ? choice
        : (options.includes(previousReason) ? previousReason : (previousReason ? 'other' : ''));

    return {
        options,
        choice: previousChoice,
        customReason: previousChoice === 'other' ? previousReason : '',
        menuOpen: false,
        activeIndex: 0,

        get menuOptions() {
            return [...this.options.map(value => ({ value, label: value })),
                { value: 'other', label: 'Другая причина (указать свою)' }];
        },

        get choiceLabel() {
            return this.menuOptions.find(option => option.value === this.choice)?.label ?? 'Выберите причину';
        },

        openMenu() {
            this.activeIndex = Math.max(0, this.menuOptions.findIndex(option => option.value === this.choice));
            this.menuOpen = true;
            this.$nextTick(() => {
                this.$refs.reasonList.focus();
                this.scrollToActive();
            });
        },

        closeMenu(restoreFocus = false) {
            this.menuOpen = false;
            if (restoreFocus) this.$refs.reasonTrigger.focus();
        },

        moveOption(index) {
            this.activeIndex = Math.max(0, Math.min(this.menuOptions.length - 1, index));
            this.scrollToActive();
        },

        scrollToActive() {
            this.$nextTick(() => this.$refs.reasonList.querySelectorAll('[role="option"]')[this.activeIndex]?.scrollIntoView({ block: 'nearest' }));
        },

        chooseOption(index) {
            const option = this.menuOptions[index];
            if (!option) return;
            this.choice = option.value;
            this.closeMenu(true);
        },

        get selectedReason() {
            if (this.choice === 'other') return this.customReason.trim();
            return this.options.includes(this.choice) ? this.choice : '';
        },

        get canSubmit() {
            const length = Array.from(this.selectedReason).length;
            return length > 0 && length <= 700;
        },
    };
}
