do (jQuery) ->
   # Add link to attachment-details template
   template = jQuery("#tmpl-attachment-details").text()
   injectTemplate = """
      <a target="_blank" href="upload.php?page=pte-edit&post={{data.id}}">
         #{objectL10n.PTE}
      </a>
   """
   template = template.replace(/(<div class="compat-meta">)/, "#{injectTemplate}\n$1")
   jQuery("#tmpl-attachment-details").text(template)
