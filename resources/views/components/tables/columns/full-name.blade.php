<div class="flex items-center gap-2.5">
    <img alt="" class="rounded-full size-9 shrink-0" src="{{ Vite::image('avatars/' . $model->getAvatar()) }}">
    <div class="flex flex-col">
        <span class="text-sm font-medium text-gray-900 mb-px">
            {{ $model->full_name }}
        </span>
        <span class="text-2sm text-gray-700 font-normal">
            {{ $model->email }}
        </span>
    </div>
</div>
