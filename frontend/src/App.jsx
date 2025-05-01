import {
  BrowserRouter as Router,
  Routes,
  Route,
  useLocation,
} from "react-router-dom";
import Header from "./components/Header";
import Footer from "./components/Footer";
import Home from "./components/pages/Home";
import About from "./components/pages/About";
import Services from "./components/pages/Services";
import Tickets from "./components/pages/Tickets";
import News from "./components/pages/News";
import Contact from "./components/pages/Contact";
import Careers from "./components/pages/Careers";
import Orders from "./components/pages/Orders";
import SearchResults from "./components/pages/SearchResults";
import SignUp from "./components/Account/SignUp";
import SignIn from "./components/Account/SignIn";
import { AuthProvider } from "./components/contexts/AuthContext";
import PrivateRoute from "./components/PrivateRoute";
import ForgotPassword from "./components/Account/ForgotPassword";
import ResetPassword from "./components/Account/ResetPassword";
import ScrollToTop from "./components/ScrollToTop";
import PaymentStatus from "./components/PaymentStatus";
import CSKH from "./components/CSKH";





function Layout() {
  const location = useLocation();
  return (
    <>
      {location.pathname !== "/signup" && location.pathname !== "/signin" && (
        <Header />
      )}
      <CSKH/>
      <main className="container mx-auto font-poppins">
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/about" element={<About />} />
          <Route path="/services" element={<Services />} />
          <Route path="/tickets" element={<Tickets />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/news" element={<News />} />
          <Route path="/contact" element={<Contact />} />
          <Route path="/careers" element={<Careers />} />
          <Route
            path="/search"
            element={
              <PrivateRoute>
                <SearchResults />
              </PrivateRoute>
            }
          />
          <Route path="/payment-status" element={<PaymentStatus />} />
          <Route path="/signin" element={<SignIn />} />
          <Route path="/signup" element={<SignUp />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
        </Routes>
      </main>
      {location.pathname !== "/signup" && location.pathname !== "/signin" && (
        <Footer />
      )}
    </>
  );
}

function App() {
  return (
    <Router>
      <ScrollToTop />
      <AuthProvider>
        <Layout />
      </AuthProvider>
    </Router>
  );
}
export default App;
