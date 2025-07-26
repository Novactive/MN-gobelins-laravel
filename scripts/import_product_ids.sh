#!/bin/bash

#Pour extraire les ids des produits en echec d'import depuis le fichier log :
# grep -o 'Product([0-9]\+)' worker_23_07_2025.log | sed 's/Product(\([0-9]\+\))/\1/' | sort -n | uniq > product_ids.txt
# Vérification des arguments
if [ $# -ne 1 ]; then
  echo "Usage: $0 script/product_ids.txt"
  exit 1
fi

FICHIER="$1"

if [ ! -f "$FICHIER" ]; then
  echo "Fichier non trouvé : $FICHIER"
  exit 2
fi

while IFS= read -r id
 do
  # Ignore les lignes vides ou les commentaires
  if [[ -z "$id" || "$id" =~ ^# ]]; then
    continue
  fi
  echo "Import de l'id : $id"
  php artisan gobelins:import:zetcom --all --limit=10 --offset=0 --objectIds="$id"
done < "$FICHIER" 