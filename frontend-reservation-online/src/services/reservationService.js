import axiosClient from "@/plugins/axios";

export default {
  fetchAll() {
    return axiosClient.get("/responsable/reservations");
  },
  confirmer(id) {
    return axiosClient.patch(`/responsable/reservations/${id}/confirmer`);
  },
  rejeter(id) {
    return axiosClient.patch(`/responsable/reservations/${id}/rejeter`);
  },
  creerManuelle(payload) {
    return axiosClient.post("/responsable/reservations", payload);
  },
  annuler(id) {
    return axiosClient.patch(`/responsable/reservations/${id}/annuler`);
  },
  terminer(id) {
    return axiosClient.patch(`/responsable/reservations/${id}/terminer`);
  },
};
