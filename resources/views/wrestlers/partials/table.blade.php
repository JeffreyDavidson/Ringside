<table class="table table-striped table-bordered table-hover" id="kt_table_1">
    <thead>
        <tr>
            <th>Wrestler ID</th>
            <th>Wrestler Name</th>
            <th>Hometown</th>
            <th>Hired Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($wrestlers as $wrestler)
        <tr>
            <td>{{ $wrestler->id }}</td>
            <td>{{ $wrestler->name }}</td>
            <td>{{ $wrestler->hometown }}</td>
            <td>{{ $wrestler->formatted_hired_at }}</td>
            <td>{{ $wrestler->status }}</td>
            <td nowrap>
                <span style="overflow: visible; position: relative; width: 80px;">
                    <div class="dropdown">
                        <a data-toggle="dropdown" class="btn btn-sm btn-clean btn-icon btn-icon-md" aria-expanded="false">
                            <i class="flaticon-more-1"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" style="display: none; position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-22.1px, 32.5px, 0px);" x-placement="bottom-end">
                            <ul class="kt-nav">
                                @can('view', $wrestler)
                                    <li class="kt-nav__item">
                                        <a class="kt-nav__link" href="{{ route('wrestlers.show', $wrestler) }}">
                                            <i class="kt-nav__link-icon flaticon2-expand"></i>
                                            <span class="kt-nav__link-text">View</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('update', $wrestler)
                                    <li class="kt-nav__item">
                                        <a class="kt-nav__link" href="{{ route('wrestlers.edit', $wrestler) }}">
                                            <i class="kt-nav__link-icon flaticon2-contract"></i>
                                            <span class="kt-nav__link-text">Edit</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('delete', $wrestler)
                                    <li class="kt-nav__item">
                                        <form class="d-inline-block" action="{{ route('wrestlers.destroy', $wrestler) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-clean btn-icon btn-icon-md" title="Delete">
                                                <i class="kt-nav__link-icon flaticon2-trash"></i>
                                            </button>
                                        </form>
                                        <a class="kt-nav__link" href="#">
                                            <i class="kt-nav__link-icon flaticon2-trash"></i>
                                            <span class="kt-nav__link-text">Delete</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </div>
                </span>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5">There are currently 0 wrestlers in the system.</td>
        </tr>
    @endforelse
    </tbody>
</table>
