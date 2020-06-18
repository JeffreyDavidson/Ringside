<div class="kt-aside  kt-aside--fixed  kt-grid__item kt-grid kt-grid--desktop kt-grid--hor-desktop" id="kt_aside">
    <div class="kt-aside__brand kt-grid__item " id="kt_aside_brand">
        <div class="kt-aside__brand-logo"></div>
        <div class="kt-aside__brand-tools">
            <button class="kt-aside__brand-aside-toggler kt-aside__brand-aside-toggler--left" id="kt_aside_toggler">
                <span></span>
            </button>
        </div>
    </div>

    <div class="kt-aside-menu-wrapper kt-grid__item kt-grid__item--fluid" id="kt_aside_menu_wrapper">
        <div id="kt_aside_menu" class="kt-aside-menu" data-ktmenu-vertical="1" data-ktmenu-scroll="1" data-ktmenu-dropdown-timeout="500">
            <ul class="kt-menu__nav">
                <li class="kt-menu__item {{ set_active('dashboard') }}" aria-haspopup="true">
                    <a href="{{ route('dashboard') }}" class="kt-menu__link">
                        <i class="kt-menu__link-icon flaticon-dashboard"></i>
                        <span class="kt-menu__link-text">Dashboard</span>
                    </a>
                </li>
                <li class="kt-menu__section">
                    <h4 class="kt-menu__section-text">Booking</h4>
                    <i class="kt-menu__section-icon flaticon-more-v2"></i>
                </li>
                <li class="kt-menu__item
                    {{ set_active([
                        'events.index', 'events.create', 'events.edit'
                    ]) }}"
                    aria-haspopup="true">
                    <a href="{{ route('events.index') }}" class="kt-menu__link">
                        <i class="kt-menu__link-icon flaticon-event-calendar-symbol"></i>
                        <span class="kt-menu__link-text">Events</span>
                    </a>
                </li>
                <li class="kt-menu__section">
                    <h4 class="kt-menu__section-text">Administrator</h4>
                    <i class="kt-menu__section-icon flaticon-more-v2"></i>
                </li>
                <li class="kt-menu__item kt-menu__item--submenu kt-menu__item kt-menu__item--submenu
                    {{ set_open([
                        'wrestlers.index', 'wrestlers.create', 'wrestlers.edit',
                        'tag-teams.index', 'tag-teams.create', 'tag-teams.edit',
                        'managers.index', 'managers.create', 'managers.edit',
                        'referees.index', 'referees.create', 'referees.edit',
                        'stables.index', 'stables.create', 'stables.edit'
                    ]) }}"
                    aria-haspopup="true" data-ktmenu-submenu-toggle="hover">
                    <a href="javascript:;" class="kt-menu__link kt-menu__toggle">
                        <i class="kt-menu__link-icon flaticon-users-1"></i>
                        <span class="kt-menu__link-text">Roster</span>
                        <i class="kt-menu__ver-arrow la la-angle-right"></i>
                    </a>
                    <div class="kt-menu__submenu">
                        <span class="kt-menu__arrow"></span>
                        <ul class="kt-menu__subnav">
                            <li class="kt-menu__item  kt-menu__item--parent" aria-haspopup="true">
                                <span class="kt-menu__link">
                                    <span class="kt-menu__link-text">Roster</span>
                                </span>
                            </li>
                            <li class="kt-menu__item
                                {{ set_active([
                                    'wrestlers.index', 'wrestlers.create', 'wrestlers.edit'
                                ]) }}"
                                aria-haspopup="true">
                                <a href="{{ route('wrestlers.index') }}" class="kt-menu__link">
                                    <i class="kt-menu__link-bullet kt-menu__link-bullet--dot"><span></span></i>
                                    <span class="kt-menu__link-text">Wrestlers</span>
                                </a>
                            </li>
                            <li class="kt-menu__item
                                {{ set_active([
                                    'tag-teams.index', 'tag-teams.create', 'tag-teams.edit'
                                ]) }}" aria-haspopup="true">
                                <a href="{{ route('tag-teams.index') }}" class="kt-menu__link">
                                    <i class="kt-menu__link-bullet kt-menu__link-bullet--dot"><span></span></i>
                                    <span class="kt-menu__link-text">Tag Teams</span>
                                </a>
                            </li>
                            <li class="kt-menu__item
                                {{ set_active([
                                    'managers.index', 'managers.create', 'managers.edit'
                                ]) }}"
                                aria-haspopup="true">
                                <a href="{{ route('managers.index') }}" class="kt-menu__link">
                                    <i class="kt-menu__link-bullet kt-menu__link-bullet--dot"><span></span>
                                    </i><span class="kt-menu__link-text">Managers</span>
                                </a>
                            </li>
                            <li class="kt-menu__item
                                {{ set_active([
                                    'referees.index', 'referees.create', 'referees.edit'
                                ]) }}"
                                aria-haspopup="true">
                                <a href="{{ route('referees.index') }}" class="kt-menu__link">
                                    <i class="kt-menu__link-bullet kt-menu__link-bullet--dot"><span></span></i>
                                    <span class="kt-menu__link-text">Referees</span>
                                </a>
                            </li>
                            <li class="kt-menu__item
                                {{ set_active([
                                    'stables.index', 'stables.create', 'stables.edit'
                                ]) }}"
                                aria-haspopup="true">
                                <a href="{{ route('stables.index') }}" class="kt-menu__link">
                                    <i class="kt-menu__link-bullet kt-menu__link-bullet--dot"><span></span></i>
                                    <span class="kt-menu__link-text">Stables</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="kt-menu__item
                    {{ set_active([
                        'titles.index', 'titles.create', 'titles.edit'
                    ]) }}"
                    aria-haspopup="true">
                    <a href="{{ route('titles.index') }}" class="kt-menu__link">
                        <i class="kt-menu__link-icon flaticon-trophy"></i>
                        <span class="kt-menu__link-text">Titles</span>
                    </a>
                </li>
                <li class="kt-menu__item
                    {{ set_active([
                        'venues.index', 'venues.create', 'venues.edit'
                    ]) }}"
                    aria-haspopup="true">
                    <a href="{{ route('venues.index') }}" class="kt-menu__link">
                        <i class="kt-menu__link-icon flaticon2-architecture-and-city"></i>
                        <span class="kt-menu__link-text">Venues</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
