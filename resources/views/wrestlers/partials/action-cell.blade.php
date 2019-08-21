<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                @viewbutton(['route' => route('wrestlers.show', $model)])
                @endviewbutton
            @endcan
            @can('update', $model)
                @editbutton(['route' => route('wrestlers.edit', $model)])
                @endeditbutton
            @endcan
            @can('delete', $model)
                @deletebutton(['route' => route('wrestlers.destroy', $model)])
                @enddeletebutton
            @endcan
            @can('retire', $model)
                @retirebutton(['route' => route('wrestlers.retire', $model)])
                @endretirebutton
            @endcan
            @can('unretire', $model)
                @unretirebutton(['route' => route('wrestlers.unretire', $model)])
                @endunretirebutton
            @endcan
            @can('activate', $model)
                @activatebutton(['route' => route('wrestlers.activate', $model)])
                @endactivatebutton
            @endcan
            @can('suspend', $model)
                @suspendbutton(['route' => route('wrestlers.suspend', $model)])
                @endsuspendbutton
            @endcan
            @can('reinstate', $model)
                @reinstatebutton(['route' => route('wrestlers.reinstate', $model)])
                @endreinstatebutton
            @endcan
            @can('injure', $model)
                @injurebutton(['route' => route('wrestlers.injure', $model)])
                @endinjurebutton
            @endcan
            @can('recover', $model)
                @recoverbutton(['route' => route('wrestlers.recover', $model)])
                @endrecoverbutton
            @endcan
        </ul>
    </div>
</div>
