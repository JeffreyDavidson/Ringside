<span class="badge badge-{{ $row->status->color() }} badge-outline rounded-[30px]">
    <span class="size-1.5 rounded-full bg-{{ $row->status->color() }} me-1.5"></span>
    {{ $row->status->name }}
</span>
