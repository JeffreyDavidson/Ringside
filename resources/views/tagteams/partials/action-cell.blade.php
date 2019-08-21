<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                @viewbutton(['route' => route('tagteams.show', $model)])
                @endviewbutton
            @endcan
            @can('update', $model)
                @editbutton(['route' => route('tagteams.edit', $model)])
                @endeditbutton
            @endcan
            @can('delete', $model)
                @deletebutton(['route' => route('tagteams.destroy', $model)])
                @enddeletebutton
            @endcan
            @can('retire', $model)
                @retirebutton(['route' => route('tagteams.retire', $model)])
                @endretirebutton
            @endcan
            @can('unretire', $model)
                @unretirebutton(['route' => route('tagteams.unretire', $model)])
                @endunretirebutton
            @endcan
            @can('activate', $model)
                @activatebutton(['route' => route('tagteams.activate', $model)])
                @endactivatebutton
            @endcan
            @can('suspend', $model)
                @suspendbutton(['route' => route('tagteams.suspend', $model)])
                @endsuspendbutton
            @endcan
            @can('reinstate', $model)
                @reinstatebutton(['route' => route('tagteams.reinstate', $model)])
                @endreinstatebutton
            @endcan
        </ul>
    </div>
</div>
