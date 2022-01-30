read -p "Authentication key: " varname

cat <<EOF >AuthKey
$varname
EOF
truncate -s-1 AuthKey