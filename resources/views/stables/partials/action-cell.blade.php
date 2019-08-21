<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                @viewbutton(['route' => route('stables.show', $model)])
                @endviewbutton
            @endcan
            @can('update', $model)
                @editbutton(['route' => route('stables.edit', $model)])
                @endeditbutton
            @endcan
            @can('delete', $model)
                @deletebutton(['route' => route('stables.destroy', $model)])
                @enddeletebutton
            @endcan
            @can('retire', $model)
                @retirebutton(['route' => route('stables.retire', $model)])
                @endretirebutton
            @endcan
            @can('unretire', $model)
                @unretirebutton(['route' => route('stables.unretire', $model)])
                @endunretirebutton
            @endcan
            @can('activate', $model)
                @activatebutton(['route' => route('stables.activate', $model)])
                @endactivatebutton
            @endcan
            @can('disassemble', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('stables.disassemble', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-power"></i>
                            <span class="kt-nav__link-text">Disassemble</span>
                        </button>
                    </form>
                </li>
            @endcan
        </ul>
    </div>
</div>
