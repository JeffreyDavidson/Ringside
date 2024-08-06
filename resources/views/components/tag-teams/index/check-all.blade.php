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
                this.$wire.$watch('selectedTagTeamIds', () => {
                    this.updateCheckAllState()
                })

                this.$wire.$watch('tagTeamIdsOnPage', () => {
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
                return this.$wire.tagTeamIdsOnPage.every(id => this.$wire.selectedTagTeamIds.includes(id))
            },

            pageIsEmpty() {
                return this.$wire.selectedTagTeamIds.length === 0
            },

            handleCheck(e) {
                e.target.checked ? this.selectAll() : this.deselectAll();
            },

            selectAll() {
                this.$wire.tagTeamIdsOnPage.forEach(id => {
                    if (this.$wire.selectedTagTeamIds.includes(id)) return

                    this.$wire.selectedTagTeamIds.push(id)
                })
            },

            deselectAll() {
                this.$wire.selectedTagTeamIds = []
            },
        }
    });
</script>
@endscript
