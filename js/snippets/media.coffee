do (jQuery) ->
   # Add link to attachment-details template
   injectTemplate = _.template """
      <a target="_blank" href="#{pteL10n.url}">
         #{pteL10n.PTE}
      </a>
   """, { id: '{{data.id}}' }

   template = jQuery("#tmpl-attachment-details").text()
   template = template.replace(/(<div class="compat-meta">)/, "#{injectTemplate}\n$1")
   jQuery("#tmpl-attachment-details").text(template)

   return
