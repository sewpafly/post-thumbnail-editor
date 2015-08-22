#!/bin/bash

print_usage() {
  echo "Usage: $0 [-l] <domain> <id>"
  echo "  -l   Prompt for login information and set local cookies"
  exit 1
}

# check input options
args=()
while [[ $1 ]]; do
  case "$1" in
    -l) LOGIN=1;;
	--help) ;&
	-h) print_usage;;
    *) args+=($1);;
  esac
  shift
done

if [[ ${#args} -lt 2 ]]
then
  print_usage $0
fi
#echo "${args[@]}"

domain=${args[0]:-}
id=${args[1]:-}
cookies="/tmp/pte-cookies"

login() {
  echo "[*] Logging in to http://${domain}/"

  read -e -s -p "  [-] Username: " username
  echo ""
  read -e -s -p "  [-] Password: " password
  echo ""

  curl -X POST -s \
    -d "log=${username}" \
    -d "pwd=${password}" \
    -d "testcookie=1" \
    -d "wp-submit=Log+In" \
    -b "${cookies}" -c "${cookies}" \
    "http://${domain}/wp-login.php"
}

if [[ ! -z ${LOGIN:-} ]]; then
  login
fi

get_thumbnail_info() {
  url="http://${domain}/wp-admin/admin-ajax.php?action=pte_api&pte-action=get-thumbnail-info&id=${id}"
  echo "[*] Getting thumbnail info"
  printf "   [*] URL: [%s]\n" $url
  curl -v -s -b "${cookies}" "${url}" | json2yaml
}

case "$action" in
  * )
    get_thumbnail_info;;
esac

#rm $cookies
