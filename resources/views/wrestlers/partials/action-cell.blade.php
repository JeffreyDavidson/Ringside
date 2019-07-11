<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                <li class="kt-nav__item">
                    <a href="{{ route('wrestlers.show', $model) }}" class="kt-nav__link">
                        <i class="kt-nav__link-icon flaticon2-expand"></i>
                        <span class="kt-nav__link-text">View</span>
                    </a>
                </li>
            @endcan
            @can('update', $model)
                <li class="kt-nav__item">
                    <a href="{{ route('wrestlers.edit', $model) }}" class="kt-nav__link">
                        <i class="kt-nav__link-icon flaticon2-contract"></i>
                        <span class="kt-nav__link-text">Edit</span>
                    </a>
                </li>
            @endcan
            @can('delete', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.destroy', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('DELETE')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-trash"></i>
                            <span class="kt-nav__link-text">Delete</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('retire', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.retire', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-time"></i>
                            <span class="kt-nav__link-text">Retire</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('unretire', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.unretire', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-reload"></i>
                            <span class="kt-nav__link-text">Unretire</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('activate', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.activate', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-power"></i>
                            <span class="kt-nav__link-text">Activate</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('suspend', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.suspend', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-power"></i>
                            <span class="kt-nav__link-text">Suspend</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('reinstate', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.reinstate', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-power"></i>
                            <span class="kt-nav__link-text">Reinstate</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('injure', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.injure', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-plus-1 "></i>
                            <span class="kt-nav__link-text">Injure</span>
                        </button>
                    </form>
                </li>
            @endcan
            @can('recover', $model)
                <li class="kt-nav__item">
                    <form action="{{ route('wrestlers.recover', $model) }}" method="post" class="kt-nav__link">
                        @csrf
                        @method('PUT')
                        <button class="btn w-100 text-left p-0">
                            <i class="kt-nav__link-icon flaticon2-help"></i>
                            <span class="kt-nav__link-text">Recover</span>
                        </button>
                    </form>
                </li>
            @endcan
        </ul>
    </div>
</div>
