
<form class="ui large grey form" id="module-quality-assessment-form">

<div class="ui top attached tabular menu">
  <a class="active item" data-tab="first">{{ t._('module_quality_Questions') }}</a>
  <a class="item" data-tab="second">{{ t._('module_quality_ApiKeys') }}</a>
  <a class="item" data-tab="сontrol-phrases">{{ t._('module_quality_сontrol_phrases') }}</a>
</div>
<div class="ui bottom attached active tab segment" data-tab="first">
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
</div>


<div class="ui bottom attached tab segment" data-tab="second">
      {{ form.render('id') }}
      <div class="field">
          <div class="ui segment">
              <div class="ui toggle checkbox">
                  <label>{{ t._('module_quality_useTts') }}</label>
                  {{ form.render('useTts') }}
              </div>
          </div>
      </div>
      <div class="ten wide field">
          <label>{{ t._('module_quality_ttsEngine') }}</label>
          {{ form.render('ttsEngine') }}
      </div>
      <div class="ten wide field">
          <label >{{ t._('module_quality_yandexApiKey') }}</label>
          {{ form.render('yandexApiKey') }}
      </div>
      <div class="ten wide field">
          <label >{{ t._('module_quality_yandexFolderId') }}</label>
          {{ form.render('yandexFolderId') }}
      </div>
      <div class="ten wide field">
          <label >{{ t._('module_quality_tinkoffApiKey') }}</label>
          {{ form.render('tinkoffApiKey') }}
      </div>
      <div class="ten wide field">
          <label >{{ t._('module_quality_tinkoffSecretKey') }}</label>
          {{ form.render('tinkoffSecretKey') }}
      </div>

</div>
<div class="ui bottom attached tab segment" data-tab="сontrol-phrases">
    <div class="ui message">
      <div class="header">
        {{ t._('module_quality_attention') }}
      </div>
      <p>{{ t._('module_quality_attention_text') }}</p>
    </div>
     <div class="ten wide field">
       <label >{{ t._('module_quality_pressed5') }}</label>
       {{ form.render('pressed5') }}
     </div>
     <div class="ten wide field">
       <label >{{ t._('module_quality_pressed4') }}</label>
       {{ form.render('pressed4') }}
     </div>
    <div class="ten wide field">
      <label >{{ t._('module_quality_pressed3') }}</label>
      {{ form.render('pressed3') }}
    </div>
    <div class="ten wide field">
      <label >{{ t._('module_quality_pressed2') }}</label>
      {{ form.render('pressed2') }}
    </div>
    <div class="ten wide field">
      <label >{{ t._('module_quality_pressed1') }}</label>
      {{ form.render('pressed1') }}
    </div>

</div>
{{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>