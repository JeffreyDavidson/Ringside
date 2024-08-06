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
                this.$wire.$watch('selectedVenueIds', () => {
                    this.updateCheckAllState()
                })

                this.$wire.$watch('venueIdsOnPage', () => {
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
                return this.$wire.venueIdsOnPage.every(id => this.$wire.selectedVenueIds.includes(id))
            },

            pageIsEmpty() {
                return this.$wire.selectedVenueIds.length === 0
            },

            handleCheck(e) {
                e.target.checked ? this.selectAll() : this.deselectAll();
            },

            selectAll() {
                this.$wire.venueIdsOnPage.forEach(id => {
                    if (this.$wire.selectedVenueIds.includes(id)) return

                    this.$wire.selectedVenueIds.push(id)
                })
            },

            deselectAll() {
                this.$wire.selectedVenueIds = []
            },
        }
    });
</script>
@endscript
