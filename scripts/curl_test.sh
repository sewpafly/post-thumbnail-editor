#!/bin/bash

print_usage() {
  echo "Usage: $0 [-l] <domain> <id>"
  echo "  -l            Prompt for login information and set local cookies"
  echo "  --help        Show help"
  echo "  -r  --resize  Run the resize command, defaults (x:0 y:0 w:100 h:100)"
  echo "  -x            (Resize) Set the x parameter"
  echo "  -y            (Resize) Set the y parameter"
  echo "  -w            (Resize) Set the w parameter"
  echo "  -h            (Resize) Set the h parameter"
  echo "  --sizes       comma-separated list of sizes"
  echo "  -c  --confirm Run the confirm command, requires --files option"
  echo "  -f, --file    <size> <path>, can be repeated"
  echo "  -i, --info    Get attachment information"
  exit 1
}

# check input options
args=()
declare -A files # associative array
while [[ $1 ]]; do
  case "$1" in
    -l) LOGIN=1;;
    --help) print_usage;;
    --resize) ;&
    -r) action="resize";;
    -x) x="${2}" && shift;;
    -y) y="${2}" && shift;;
    -h) h="${2}" && shift;;
    -w) w="${2}" && shift;;
    --sizes) sizes="${2}" && shift;;
    --confirm) ;&
    -c) action="confirm";;
    -f) ;&
    --file) files["${2}"]=${3} && shift && shift;;
    -i) ;&
    --info) action="info";;
    *) args+=($1);;
  esac
  shift
done

if [[ ${#args} -lt 2 ]]
then
  print_usage $0
fi
#echo "${!files[@]}"

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

get_image_info() {
  url="http://${domain}/wp-admin/admin-ajax.php?action=pte_api&pte-action=get-image-info&id=${id}"
  echo "[*] Getting info"
  printf "   [*] URL: [%s]\n" $url
  curl -v -s -b "${cookies}" "${url}" | json2yaml
}

resize() {
  echo "[*] RESIZING: x:$x y:$y w:$w h:$h sizes:$sizes"
  printf -v args "x=${x}&y=${y}&w=${w}&h=${h}&sizes=${sizes}"
  url="http://${domain}/wp-admin/admin-ajax.php?action=pte_api&pte-action=resize-thumbnails&id=${id}&${args}"
  printf "   [*] URL: [%s]\n" $url
  curl -v -s -b "${cookies}" "${url}" | json2yaml
}

confirm() {
  if [[ ${#files[@]} -eq 0 ]]; then
    echo " *"
    echo " * Missing file options"
    echo " *"
    print_usage
  fi
  echo "[*] Confirming"
  args=""
  for size in "${!files[@]}"; do
    printf "   [*] %s: '%s'\n" $size ${files["$size"]}
    args+="&files%5B${size}%5D=${files["$size"]}"
  done
  url="http://${domain}/wp-admin/admin-ajax.php?action=pte_api&pte-action=confirm-images&id=${id}${args}"
  printf "   [*] URL: [%s]\n" $url
  curl -v -s -b "${cookies}" "${url}" | json2yaml
}

case "$action" in
  resize )
    resize ${w:=100} ${h:=100} ${x:=0} ${y:=0} ${sizes:=thumbnail};;
  confirm )
    confirm ;;
  info )
    get_image_info ;;
  * )
    get_thumbnail_info;;
esac

#rm $cookies
