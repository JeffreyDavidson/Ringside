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
                this.$wire.$watch('selectedManagerIds', () => {
                    this.updateCheckAllState()
                })

                this.$wire.$watch('managerIdsOnPage', () => {
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
                return this.$wire.managerIdsOnPage.every(id => this.$wire.selectedManagerIds.includes(id))
            },

            pageIsEmpty() {
                return this.$wire.selectedManagerIds.length === 0
            },

            handleCheck(e) {
                e.target.checked ? this.selectAll() : this.deselectAll();
            },

            selectAll() {
                this.$wire.managerIdsOnPage.forEach(id => {
                    if (this.$wire.selectedManagerIds.includes(id)) return

                    this.$wire.selectedManagerIds.push(id)
                })
            },

            deselectAll() {
                this.$wire.selectedManagerIds = []
            },
        }
    });
</script>
@endscript
