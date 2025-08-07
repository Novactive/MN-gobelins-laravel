#!/bin/bash

# Configuration du projet
PROJECT_PATH="/home/collection/www"

# Génération automatique du fichier product_ids.txt avec date dynamique
echo "Génération du fichier product_ids.txt avec les IDs en échec..."

# Obtenir la date actuelle au format DD_MM_YYYY
DATE=$(date +%d_%m_%Y)
echo "Date utilisée: $DATE"

# Générer le fichier product_ids.txt depuis les logs
grep -o 'Product([0-9]\+)' ${PROJECT_PATH}/storage/logs/worker_${DATE}.log | sed 's/Product(\([0-9]\+\))/\1/' | sort -n | uniq > ${PROJECT_PATH}/scripts/product_ids.txt

echo "Fichier ${PROJECT_PATH}/scripts/product_ids.txt généré avec succès"

# Aller dans le répertoire de l'application
cd ${PROJECT_PATH}

# Vérification du fichier généré
FICHIER="${PROJECT_PATH}/scripts/product_ids.txt"

if [ ! -f "$FICHIER" ]; then
  echo "Fichier non trouvé : $FICHIER"
  exit 2
fi

echo "Début du traitement des IDs en échec..."
echo "Nombre d'IDs à traiter: $(wc -l < "$FICHIER")"

while IFS= read -r id
do
  # Ignore les lignes vides ou les commentaires
  if [ -z "$id" ] || echo "$id" | grep -q '^#'; then
    continue
  fi
  echo "Import de l'id : $id"
  php artisan gobelins:import:zetcom --all --limit=10 --offset=0 --objectIds="$id"
done < "$FICHIER"

echo "Traitement terminé" 