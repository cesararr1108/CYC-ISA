class StorageManager {
    constructor(storageKey) {
      // El storageKey es para que podamos manejar distintos 'storage' si lo necesitamos
      this.storageKey = storageKey;
    }
  
    // Método para verificar si el item existe en el localStorage
    itemExists(key) {
      return localStorage.getItem(this.storageKey) && localStorage.getItem(this.storageKey).includes(key);
    }
  
    // Crear un nuevo item o agregar uno si ya existe
    createItem(key, value) {
      let storageData = this._getStorageData();
      storageData[key] = value;
      localStorage.setItem(this.storageKey, JSON.stringify(storageData));
    }
  
    // Buscar un item por su clave
    getItem(key) {
      let storageData = this._getStorageData();
      return storageData[key] || null;  // Devuelve null si no existe
    }
  
    // Eliminar un item por su clave
    removeItem(key) {
      let storageData = this._getStorageData();
      if (key in storageData) {
        delete storageData[key];
        localStorage.setItem(this.storageKey, JSON.stringify(storageData));
      }
    }
  
    // Editar un item
    editItem(key, newValue) {
      let storageData = this._getStorageData();
      if (key in storageData) {
        storageData[key] = newValue;
        localStorage.setItem(this.storageKey, JSON.stringify(storageData));
      }
    }
  
    // Obtener todos los items almacenados
    getAllItems() {
      return this._getStorageData();
    }
  
    // Método privado para obtener los datos almacenados, o un objeto vacío si no existen
    _getStorageData() {
      const data = localStorage.getItem(this.storageKey);
      return data ? JSON.parse(data) : {};
    }
  }
  