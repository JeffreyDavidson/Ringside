<div x-data="checkAll">
    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
        <input x-ref="checkbox" @change="handleCheck" type="checkbox" class="form-check-input"/>
    </div>
</div>

@script
<script>
    Alpine.data('checkAll', () => {
        return {
            init() {
                this.$wire.$watch('selectedRefereeIds', () => {
                    this.updateCheckAllState()
                })

                this.$wire.$watch('refereeIdsOnPage', () => {
                    this.updateCheckAllState()
                })
            },

            updateCheckAllState() {
                if (this.pageIsSelected()) {
                    this.$refs.checkbox.checked = true
                    this.$refs.checkbox.indeterminate = false
                } else if (this.pageIsEmpty()) {
                    this.$refs.checkbox.checked = false
                    this.$refs.checkbox.indeterminate = false
                } else {
                    this.$refs.checkbox.checked = false
                    this.$refs.checkbox.indeterminate = true
                }
            },

            pageIsSelected() {
                return this.$wire.refereeIdsOnPage.every(id => this.$wire.selectedRefereeIds.includes(id))
            },

            pageIsEmpty() {
                return this.$wire.selectedRefereeIds.length === 0
            },

            handleCheck(e) {
                e.target.checked ? this.selectAll() : this.deselectAll();
            },

            selectAll() {
                this.$wire.refereeIdsOnPage.forEach(id => {
                    if (this.$wire.selectedRefereeIds.includes(id)) return

                    this.$wire.selectedRefereeIds.push(id)
                })
            },

            deselectAll() {
                this.$wire.selectedRefereeIds = []
            },
        }
    });
</script>
@endscript
