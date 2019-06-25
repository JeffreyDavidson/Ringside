<div class="dropdown">
    <a href="javascript:;" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown">
        <i class="flaticon-more-1"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <ul class="kt-nav">
            @can('view', $model)
                <li class="kt-nav__item">
                    <a href="{{ route('titles.show', $model) }}" class="kt-nav__link">
                        <i class="kt-nav__link-icon flaticon2-expand"></i>
                        <span class="kt-nav__link-text">View</span>
                    </a>
                </li>
            @endcan
            @can('update', $model)
                <li class="kt-nav__item">
                    <a href="{{ route('titles.edit', $model) }}" class="kt-nav__link">
                        <i class="kt-nav__link-icon flaticon2-contract"></i>
                        <span class="kt-nav__link-text">Edit</span>
                    </a>
                </li>
            @endcan
        </ul>
    </div>
</div>
