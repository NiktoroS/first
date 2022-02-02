<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="">Прайс листы</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Справочники
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="javaScript:priceContent('providerRows')">Поставщики</a>
                    <a class="dropdown-item" href="javaScript:priceContent('itemRows')">Позиции</a>
                    <a class="dropdown-item" href="javaScript:priceContent('pricelistRows')">Прайс листы</a>
                    <a class="dropdown-item" href="javaScript:priceContent('priceItemLogRows')">Изменение позиций прайслиста</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Действия
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="javaScript:priceContent('pricelistRows')">Крректировка данных прайлиста</a>
                    <a class="dropdown-item" href="javaScript:priceContent('priceItemLogRows')">Изменение позиций прайслиста</a>
                    <a class="dropdown-item" href="javaScript:priceContent('pricelistRows')">Вывод прайслиста на дату</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item disabled" href="/task">Задачи</a>
                </div>
            </li>
        </ul>
{*
        <form class="form-inline my-2 my-lg-0">
            <input class="form-control mr-sm-2" type="search" placeholder="Поиск" aria-label="Поиск">
            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Поиск</button>
        </form>
*}
    </div>
</nav>
