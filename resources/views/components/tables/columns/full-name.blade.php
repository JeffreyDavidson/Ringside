<div class="flex items-center gap-2.5">
    <img alt="" class="size-9 shrink-0 rounded-full" src="{{ Vite::image('avatars/' . $model->getAvatar()) }}" />
    <div class="flex flex-col">
        <span class="mb-px text-sm font-medium text-gray-900"> {{ $model->full_name }} </span>
        <span class="text-2sm font-normal text-gray-700"> {{ $model->email }} </span>
    </div>
</div>
