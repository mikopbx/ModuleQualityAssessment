<form class="ui large grey segment form" id="module-quality-assessment-form">
    {{ form.render('id') }}
    <div class="field disability">
        <div class="ui segment">
            <div class="ui toggle checkbox">
                <label>{{ t._('module_quality_useTts') }}</label>
                {{ form.render('useTts') }}
            </div>
        </div>
    </div>
    <div class="ten wide field disability">
        <label >{{ t._('module_quality_yandexApiKey') }}</label>
        {{ form.render('yandexApiKey') }}
    </div>
    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>
<div class="ui grid">
    <div class="ui row">
        <div class="ui five wide column">
            {{ link_to("#", '<i class="add user icon"></i>  '~t._('module_template_AddNewRecord'), "class": "ui blue button", "id":"add-new-row", "id-table":"QuestionsList-table") }}
        </div>
    </div>
</div>
<br>
<table id="QuestionsList-table" class="ui small very compact single line table"></table>
<select id="sound-list" style="display: none;">
    {% for record in sounds %}
        <option value="{{ record.id }}">{{ record.name }}</option>
    {% endfor %}
</select>
<select id="role-list" style="display: none;">
    {% for record in roles %}
        <option value="{{ record.id }}">{{ record.name }}</option>
    {% endfor %}
</select>
<div id="template-select" style="display: none;">
    <div class="ui dropdown select-group" data-value="PARAM">
        <div class="text">PARAM</div>
        <i class="dropdown icon"></i>
    </div>
</div>
