<div class="my_modules_pages">
    <h2>{{ course_title }}</h2>
    
    <div class="tools_container">
        <ul>
            {% if wiki %}
            <li id="tools_wiki"><a href="{{ wiki.url }}">Wiki</a></li>
            {% endif %}
            {% if forum %}
            <li id="tools_forum"><a href="{{ forum.url }}">Forum</a></li>
            {% endif %}
            {% if tools %}
            <li id="tools_outils" class="dropdown">
                <div id="nb_tools">{{ tools|length }}</div>
                <a class="parent dropdown-toggle">Outils</a>
                <div id="other_tools_container" class="dropdown-menu">
                    <ul id="other_tools">

                            {%for tool in tools %}
                            <li {%if loop.last == true %} class="last" {% endif %}>
                                    <a href="{{ tool.url }}">{{tool.langname|get_lang}}</a>
                            </li>
                            {% endfor %}
                    </ul>
                </div>
            </li>
            {% endif %}
        </ul>
    </div>
    <div style="clear:both"></div>
    
    <div class="modules_main">
	{% for module in modules %}
	    <div class="module_block  {{ cycle(['odd','even','last'], loop.index0) }}">
                <div class="module_block_infos">
                    <h3><a href="{{ module.url }}">{{ module.lp_name}}</a></h3>
                    <div class="module-author">Par {{ module.author}}</div>
                    <div class="module-description">{{ module.description}}</div>
                </div>
                <div class="module_progress">
                    <div class="module_progress_infos">
                        {% if module.progress == "0%" %} <img src="{{'module-progress-begin.png'|icon}}" width="18" height="37" class="module_progress_img begin"/>{% endif %}
                        {% if module.progress > "0%" and module.progress != "100%" %} <img src="{{'module-progress-current'|icon}}.png" width="23" height="32" class="module_progress_img current"/>{% endif %}
                        {% if module.progress == "100%" %} <img src="{{'module-progress-end.png'|icon}}" width="22" height="22" class="module_progress_img end"/>{% endif %}
                        <div class="module_progress_percent">{{ module.progress }}</div>
                        <div style="clear:both"></div>
                    </div>
                    <div class="module_progress_bar">
                        {{ module.progress_bar }}
                    </div>
                    <div style="clear:both"></div>
                </div>
	    </div>
	{% endfor %}
        <div style="clear:both"></div>
    </div>
</div>