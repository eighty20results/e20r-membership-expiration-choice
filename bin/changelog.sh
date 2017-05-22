#!/usr/bin/env bash
#
# Copyright (C) 2016-2017  Thomas Sjolshagen - Eighty / 20 Results by Wicked Strong Chicks, LLC
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
short_name="e20r-member-cancellation-policy"
server="eighty20results.com"
sed=/usr/bin/sed
readme_path="../build_readmes/"
changelog_source=${readme_path}current.txt
incomplete_out=tmp.txt
json_out=json_changelog.txt
readme_out=readme_changelog.txt
version=$(egrep "^Version:" ../${short_name}.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
json_header="<h3>${version}</h3><ol>"
json_footer="</ol>"
readme_header="== ${version} =="
###########
#
# Create a metadata.json friendly changelog entry for the current ${version}
#
${sed} -e"s/\"/\'/g" -e's/.*/\<li\>&\<\/li\>/' -e's/\\/\\\\\\\\/g' ${changelog_source} > ${readme_path}${incomplete_out}
echo -n ${json_header} > ${readme_path}${json_out}
cat ${readme_path}${incomplete_out} | tr -d '\n' >> ${readme_path}${json_out}
echo -n ${json_footer} >> ${readme_path}${json_out}
rm ${readme_path}${incomplete_out}
###########
#
# Create a README.txt friendly changelog entry for the current ${version}
#
echo ${readme_header} > ${readme_path}${readme_out}
echo '' >> ${readme_path}${readme_out}
${sed} -e"s/\"/\'/g" -e"s/.*/\*\ &/" ${changelog_source} >> ${readme_path}${readme_out}
echo '' >> ${readme_path}${readme_out}

